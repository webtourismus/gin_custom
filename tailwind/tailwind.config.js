let frontendConfig = {};
try {
  frontendConfig = require('../../../../themes/custom/frontend/tailwind/tailwind-nopreflight.config.js');
}
catch (err) {}

frontendConfig.corePlugins = {
  preflight: false,
}

module.exports = frontendConfig;
