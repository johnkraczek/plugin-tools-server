import React from "react";

import SettingsProvider from "./settingContext";
import TitleProvider from "./titleContext";

const Context = ({ children }) => {
    return (
        <TitleProvider>
            <SettingsProvider>
            {children}
            </SettingsProvider>
        </TitleProvider>
    )
}

export default Context;