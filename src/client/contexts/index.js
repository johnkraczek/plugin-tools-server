import React from "react";

import PluginProvider from "./pluginContext";
import SettingsProvider from "./settingContext";
import TitleProvider from "./titleContext";

const Context = ({ children }) => {
    return (
        <TitleProvider>
            <PluginProvider>
                <SettingsProvider>
                    {children}
                </SettingsProvider>
            </PluginProvider>
        </TitleProvider>
    )
}

export default Context;