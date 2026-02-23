export const VALID_TLDS = [
  // Common TLDs
  'com', 'org', 'net', 'edu', 'gov', 'mil', 'int',
  // Country codes
  'uk', 'us', 'ca', 'de', 'fr', 'jp', 'in', 'au', 'br', 'ru', 'cn', 'es', 'it', 'nl',
  'se', 'no', 'dk', 'fi', 'pl', 'ch', 'at', 'be', 'gr', 'pt', 'ie', 'nz', 'za', 'mx',
  // New gTLDs
  'io', 'ai', 'co', 'tv', 'me', 'info', 'biz', 'name', 'mobi', 'travel', 'aero',
  'museum', 'coop', 'jobs', 'tel', 'asia', 'cat', 'pro', 'club', 'guru',
  'tech', 'store', 'online', 'site', 'app', 'dev', 'cloud', 'media', 'agency',
  'digital', 'global', 'network', 'systems', 'solutions', 'technology'
];

export const validateEmail = (_: any, value: string) => {
  if (!value) {
    return Promise.reject(new Error('Email address is required'));
  }

  // Basic email format check
  const basicEmailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!basicEmailRegex.test(value)) {
    return Promise.reject(new Error('Please enter a valid email address'));
  }

  // Extract domain
  const domainPart = value.split('@')[1];
  if (!domainPart) {
    return Promise.reject(new Error('Invalid email format'));
  }

  const domainParts = domainPart.split('.');
  if (domainParts.length < 2) {
    return Promise.reject(new Error('Email must contain a valid domain'));
  }

  // Validate TLD
  const tld = domainParts[domainParts.length - 1].toLowerCase();
  if (tld.length < 2 || tld.length > 10 || !/^[a-z]{2,10}$/.test(tld)) {
    return Promise.reject(new Error('Please enter a valid email domain'));
  }

  if (!VALID_TLDS.includes(tld)) {
    return Promise.reject(new Error('Please enter an email address with a valid domain'));
  }

  // Validate domain name
  const domainName = domainParts.slice(0, -1).join('.');
  if (!domainName || !/^[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*$/.test(domainName)) {
    return Promise.reject(new Error('Please enter a valid email domain'));
  }

  return Promise.resolve();
};
