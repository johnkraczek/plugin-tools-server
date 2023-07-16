import React from "react";

import PluginProvider from "./pluginContext";
import SettingsProvider from "./settingContext";
import TitleProvider from "./titleContext";

const Context = ({ children }) => {
    return (
        <TitleProvider>
            {/* <SettingsProvider> */}
                {/* <PluginProvider> */}
                    {children}
                {/* </PluginProvider> */}
            {/* </SettingsProvider> */}
        </TitleProvider>
    )
}

export default Context;