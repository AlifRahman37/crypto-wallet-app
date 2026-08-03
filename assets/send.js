// ============================================
// Multi-Chain Wallet — Send Transactions
// Signing always happens locally with the in-memory key.
// ============================================

async function sendEvm(chainId, toAddress, amountEth) {
  const chain = CHAINS[chainId];
  const acc = wallet.accounts[chainId];
  const provider = new ethersLib.JsonRpcProvider(chain.rpc());
  const signer = new ethersLib.Wallet(acc.privateKey, provider);
  const tx = await signer.sendTransaction({
    to: toAddress,
    value: ethersLib.parseEther(String(amountEth))
  });
  return tx.hash;
}

async function sendTron(toAddress, amountTrx, token) {
  const acc = wallet.accounts.tron;
  tronWeb.setPrivateKey(acc.privateKey);
  if (token === 'USDT') {
    const contract = await tronWeb.contract().at(CHAINS.tron.usdtContract);
    const tx = await contract.transfer(toAddress, Math.floor(amountTrx * 1e6)).send({ feeLimit: 40_000_000 });
    return tx;
  } else {
    const tx = await tronWeb.trx.sendTransaction(toAddress, Math.floor(amountTrx * 1e6), acc.privateKey);
    return tx.txid || tx.transaction?.txID;
  }
}

async function sendBitcoin(toAddress, amountBtc) {
  const acc = wallet.accounts.bitcoin;
  const url = `${CHAINS.bitcoin.api()}/addrs/${acc.address}?unspentOnly=true` + (API_KEYS.blockcypher ? `&token=${API_KEYS.blockcypher}` : '');
  const infoR = await fetch(url);
  const info = await infoR.json();
  const utxos = info.txrefs || [];
  if (!utxos.length) throw new Error(t('err_no_utxo'));

  const psbt = new window.bitcoinLib.Psbt({ network: window.bitcoinLib.networks.bitcoin });
  const keyPair = window.ECPair.fromWIF(acc.wif, window.bitcoinLib.networks.bitcoin);
  let inputSum = 0;
  const targetSats = Math.floor(amountBtc * 1e8);
  const feeSats = 2000;

  for (const u of utxos) {
    if (inputSum >= targetSats + feeSats) break;
    const txHex = await (await fetch(`${CHAINS.bitcoin.api()}/txs/${u.tx_hash}?includeHex=true`)).json();
    psbt.addInput({
      hash: u.tx_hash, index: u.tx_output_n,
      witnessUtxo: { script: window.bitcoinLib.payments.p2wpkh({ pubkey: keyPair.publicKey, network: window.bitcoinLib.networks.bitcoin }).output, value: u.value }
    });
    inputSum += u.value;
  }
  if (inputSum < targetSats + feeSats) throw new Error(t('err_insufficient'));

  psbt.addOutput({ address: toAddress, value: targetSats });
  const change = inputSum - targetSats - feeSats;
  if (change > 546) psbt.addOutput({ address: acc.address, value: change });

  psbt.signAllInputs(keyPair);
  psbt.finalizeAllInputs();
  const txHex = psbt.extractTransaction().toHex();

  const pushR = await fetch(`${CHAINS.bitcoin.api()}/txs/push` + (API_KEYS.blockcypher ? `?token=${API_KEYS.blockcypher}` : ''), {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ tx: txHex })
  });
  const pushD = await pushR.json();
  if (pushD.error) throw new Error(pushD.error);
  return pushD.tx.hash;
}

async function sendSolana(toAddress, amountSol) {
  const acc = wallet.accounts.solana;
  const connection = new solanaWeb3.Connection(CHAINS.solana.rpc());
  const secretKey = window.bs58.decode(acc.privateKey);
  const fromKeypair = solanaWeb3.Keypair.fromSecretKey(secretKey);
  const toPubkey = new solanaWeb3.PublicKey(toAddress);

  const tx = new solanaWeb3.Transaction().add(
    solanaWeb3.SystemProgram.transfer({
      fromPubkey: fromKeypair.publicKey,
      toPubkey,
      lamports: Math.floor(amountSol * solanaWeb3.LAMPORTS_PER_SOL)
    })
  );
  const sig = await solanaWeb3.sendAndConfirmTransaction(connection, tx, [fromKeypair]);
  return sig;
}

async function executeSendTx(chainId, toAddress, amount, token) {
  let txHash;
  if (CHAINS[chainId].family === 'evm') {
    txHash = await sendEvm(chainId, toAddress, amount);
  } else if (chainId === 'tron') {
    txHash = await sendTron(toAddress, amount, token);
  } else if (chainId === 'bitcoin') {
    txHash = await sendBitcoin(toAddress, amount);
  } else if (chainId === 'solana') {
    txHash = await sendSolana(toAddress, amount);
  } else {
    throw new Error('Unsupported chain');
  }

  // Log to server (address/hash only — never keys)
  try {
    await fetch(API_BASE + '?action=log_tx', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        wallet_address: wallet.accounts[chainId]?.address,
        chain: chainId, tx_hash: txHash,
        from: wallet.accounts[chainId]?.address, to: toAddress,
        token: token || CHAINS[chainId].symbol, amount, status: 'sent'
      })
    });
  } catch (e) { console.warn('log_tx failed', e); }

  return txHash;
}
