import React, { createContext, useContext, useEffect, useReducer, useState } from "react";
import axios from "axios";

import { reducer, initialFormData } from "./reducer";

const axiosOptions = {
    headers: { 'X-WP-Nonce': pts.nonce }
};
const SettingsFormEndpoint = pts.rest + 'settings';

console.log(SettingsFormEndpoint);

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
                console.log('get', res);
            })
            .catch(err => {
                console.log(err)
            })
    }, []);

    const saveSelection = () => {

        setSaving(true);

        let postData = { ...(formData.password_blurred && {bitbucket_password: formData.bitbucket_password}),
            bitbucket_username: formData.bitbucket_username,
            bitbucket_workspace: formData.bitbucket_workspace
         };

        console.log('data to save:', postData)

        axios.post(SettingsFormEndpoint, postData, axiosOptions)
            .then(res => {
                 console.log('save', res);
            })
            .catch(err => {
                console.log(err)
            })
            .finally(() => {
                setTimeout(() => {
                    setSaving(false)
                }, 1000);
            })
    }

    return (
        <SettingsContext.Provider value={{ formData, dispatch, saving, saveSelection }}>
            {children}
        </SettingsContext.Provider>
    );
};

export default SettingsProvider;