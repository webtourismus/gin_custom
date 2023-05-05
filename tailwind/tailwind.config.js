try {
  const frontendConfig = require('../../../../themes/custom/frontend/tailwind/tailwind-nopreflight.config.js');
}
catch (err) {
  const frontendConfig = {};
}

frontendConfig.corePlugins = {
  preflight: false,
}

module.exports = frontendConfig;
