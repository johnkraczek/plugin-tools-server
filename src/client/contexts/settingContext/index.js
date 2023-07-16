import React, { createContext, useContext, useEffect, useReducer, useState } from "react";
import axios from "axios";

import { reducer, initialFormData } from "./reducer";

const axiosOptions = {
    headers: { 'X-WP-Nonce': ydtb.nonce }
};

const SettingsFormEndpoint = ydtb.root + '/settings';

const SettingsContext = createContext();
export const useSettingsContext = () => useContext(SettingsContext);

const SettingsProvider = ({ children }) => {

    const [formData, dispatch] = useReducer(reducer, initialFormData);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        axios.get(SettingsFormEndpoint, axiosOptions)
            .then(res => {
                if (res.status == 200 && res.data) {
                    dispatch({ type: "INITIALIZE", value: res.data })
                }
            })
            .catch(err => {
                console.log(err)
            })
    }, []);

    const saveSelection = () => {
        setSaving(true);
        axios.post(SettingsFormEndpoint, formData, axiosOptions)
            .then(res => {
                 console.log('save', res);
            })
            .catch(err => {
                console.log(err)
            })
            .finally(() => {
                setTimeout(() => {
                    setSaving(false)
                }, 250);
            })
    }

    return (
        <SettingsContext.Provider value={{ formData, dispatch, saving, saveSelection }}>
            {children}
        </SettingsContext.Provider>
    );
};

export default SettingsProvider;