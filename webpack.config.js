/* eslint-disable */
const defaultConfig = require("@wordpress/scripts/config/webpack.config");
const { getWebpackEntryPoints } = require("@wordpress/scripts/utils/config");

function isProductionBuild() {
    return process.env.NODE_ENV === "production";
}

function getDevtool() {
    return isProductionBuild() ? false : "eval-source-map";
}

function getOptimization() {
    if (!isProductionBuild()) {
        return defaultConfig.optimization;
    }
    return {
        ...defaultConfig.optimization,
    };
}

module.exports = {
    ...defaultConfig,
    entry: {
        ...getWebpackEntryPoints("script")(),
    },
    devtool: getDevtool(),
    module: {
        ...defaultConfig.module,
        rules: [
            ...defaultConfig.module.rules,
        ],
    },
    resolve: {
        ...defaultConfig.resolve,
        extensions: [".tsx", ".ts", ".js", ".json"],
    },
    optimization: getOptimization(),
    performance: {
        ...defaultConfig.performance,
        hints: false,
    },
};