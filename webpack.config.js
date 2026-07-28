const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

/**
 * Disable warnings during release build.
 *
 * @type {{[p: string]: *}}
 */
module.exports = {
  ...defaultConfig,
  performance: {
    ...defaultConfig.performance,
    hints: false,
  },
};
