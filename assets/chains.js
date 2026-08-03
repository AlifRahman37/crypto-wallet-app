// ============================================
// Multi-Chain Wallet — Chain Configurations
// ============================================

const CHAINS = {
  ethereum: {
    id: 'ethereum', name: 'Ethereum', symbol: 'ETH', decimals: 18,
    family: 'evm', chainId: 1, color: '#627eea',
    logo: 'https://cryptologos.cc/logos/ethereum-eth-logo.png',
    explorer: 'https://etherscan.io',
    rpc: () => `https://eth-mainnet.g.alchemy.com/v2/${API_KEYS.alchemy}`,
  },
  bitcoin: {
    id: 'bitcoin', name: 'Bitcoin', symbol: 'BTC', decimals: 8,
    family: 'btc', color: '#f7931a',
    logo: 'https://cryptologos.cc/logos/bitcoin-btc-logo.png',
    explorer: 'https://www.blockchair.com/bitcoin',
    api: () => `https://api.blockcypher.com/v1/btc/main`,
  },
  solana: {
    id: 'solana', name: 'Solana', symbol: 'SOL', decimals: 9,
    family: 'sol', color: '#9945ff',
    logo: 'https://cryptologos.cc/logos/solana-sol-logo.png',
    explorer: 'https://solscan.io',
    rpc: () => `https://mainnet.helius-rpc.com/?api-key=${API_KEYS.helius}`,
  },
  tron: {
    id: 'tron', name: 'Tron', symbol: 'TRX', decimals: 6,
    family: 'tron', color: '#e8004d',
    logo: 'https://cryptologos.cc/logos/tron-trx-logo.png',
    explorer: 'https://tronscan.org',
    api: () => `https://api.trongrid.io`,
    usdtContract: 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',
  },
  linea: {
    id: 'linea', name: 'Linea', symbol: 'ETH', decimals: 18,
    family: 'evm', chainId: 59144, color: '#61dfff',
    logo: 'https://cryptologos.cc/logos/linea-logo.png',
    explorer: 'https://lineascan.build',
    rpc: () => `https://linea-mainnet.g.alchemy.com/v2/${API_KEYS.alchemy}`,
  },
  arbitrum: {
    id: 'arbitrum', name: 'Arbitrum', symbol: 'ETH', decimals: 18,
    family: 'evm', chainId: 42161, color: '#28a0f0',
    logo: 'https://cryptologos.cc/logos/arbitrum-arb-logo.png',
    explorer: 'https://arbiscan.io',
    rpc: () => `https://arb-mainnet.g.alchemy.com/v2/${API_KEYS.alchemy}`,
  },
  bnb: {
    id: 'bnb', name: 'BNB Chain', symbol: 'BNB', decimals: 18,
    family: 'evm', chainId: 56, color: '#f0b90b',
    logo: 'https://cryptologos.cc/logos/bnb-bnb-logo.png',
    explorer: 'https://bscscan.com',
    rpc: () => `https://bsc-dataseed.binance.org`,
  },
  base: {
    id: 'base', name: 'Base', symbol: 'ETH', decimals: 18,
    family: 'evm', chainId: 8453, color: '#0052ff',
    logo: 'https://cryptologos.cc/logos/base-logo.png',
    explorer: 'https://basescan.org',
    rpc: () => `https://base-mainnet.g.alchemy.com/v2/${API_KEYS.alchemy}`,
  },
  optimism: {
    id: 'optimism', name: 'OP', symbol: 'ETH', decimals: 18,
    family: 'evm', chainId: 10, color: '#ff0420',
    logo: 'https://cryptologos.cc/logos/optimism-ethereum-op-logo.png',
    explorer: 'https://optimistic.etherscan.io',
    rpc: () => `https://opt-mainnet.g.alchemy.com/v2/${API_KEYS.alchemy}`,
  },
  polygon: {
    id: 'polygon', name: 'Polygon', symbol: 'POL', decimals: 18,
    family: 'evm', chainId: 137, color: '#8247e5',
    logo: 'https://cryptologos.cc/logos/polygon-matic-logo.png',
    explorer: 'https://polygonscan.com',
    rpc: () => `https://polygon-mainnet.g.alchemy.com/v2/${API_KEYS.alchemy}`,
  },
};

const CHAIN_ORDER = ['ethereum','bitcoin','solana','tron','linea','arbitrum','bnb','base','optimism','polygon'];

// EVM chains all share one address (derived once), non-EVM chains each need their own derivation.
const EVM_CHAINS = CHAIN_ORDER.filter(id => CHAINS[id].family === 'evm');
