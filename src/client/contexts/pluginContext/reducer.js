export const pluginsReducer = (state, action) => {
    switch (action.type) {
        case "ADD_PLUGIN":
            const existingPluginIndex = state.findIndex(plugin => plugin.slug === action.plugin.slug);
            if (existingPluginIndex !== -1) {
                // If the plugin exists, update it
                return state.map((plugin, index) => {
                    if (index === existingPluginIndex) {
                        return { ...plugin, ...action.plugin };
                    }
                    return plugin;
                });
            } else {
                // If the plugin doesn't exist, add it
                return [...state, action.plugin];
            }

        case "REMOVE_PLUGIN":
            return state.filter(plugin => plugin.slug !== action.slug);

        case "UPDATE_PLUGIN":
            return state.map(plugin => {
                if (plugin.slug === action.plugin.slug) {
                    return { ...plugin, ...action.plugin };
                }
                return plugin;
            });

        case "INITIALIZE_PLUGINS":
            return [...action.plugins];

        default:
            return state;
    }
};