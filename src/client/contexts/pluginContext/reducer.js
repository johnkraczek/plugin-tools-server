
export const reducer = (state, action) => {
    switch (action.type) {
        case "CHECKED":
            return state.map((plugin) => {
                if (plugin.id === action.id) {
                    return { ...plugin, checked: action.value, blurred: true };
                }
                return plugin;
            });
        case "COMPOSER":
            return state.map((plugin) => {
                if (plugin.id === action.id) {
                    return { ...plugin, composerSlug: action.value, blurred:true }
                }
                return plugin;
            })
        case "BLURRED":
            return state.map((plugin)=>{
                if (plugin.id === action.id){
                    console.log('unblurring plugin');
                    return {...plugin, blurred:false}
                }
            })
        case "REPLACE":
            return action.data;

        default:
            return state;
    }
};

export const initialList = [
    {
        id: 0,
        checked: true,
        name: "Plugin Name",
        available: "0.0.0",
        current: "0.0.0",
        previous: "0.0.0",
        date: "-",
        composerSlug: "",
        downloadPlugin: false,
        blurred: false
    },
    {
        id: 1,
        checked: true,
        name: "Plugin Name",
        available: "0.0.0",
        current: "0.0.0",
        previous: "0.0.0",
        date: "-",
        composerSlug: "",
        downloadPlugin: false,
        blurred: false
    }
]