import React, { createContext, useContext, useEffect, useReducer, useState } from "react";
import axios from "axios";

import { reducer } from "./reducer";

const axiosOptions = {
    headers: { 'X-WP-Nonce': pts.nonce }
};
const PluginDataEndpoint = pts.rest + 'plugins';

const PluginContext = createContext();
export const usePluginContext = () => useContext(PluginContext);

const verifyPluginData = (plugins) => {
    if (!Array.isArray(plugins)) {
        throw new Error("Plugins data must be an array");
    }

    plugins.forEach((plugin, index) => {
        if (typeof plugin !== 'object') {
            throw new Error(`Plugin at index ${index} must be an object`);
        }

        const requiredKeys = ['name', 'availableVersion', 'currentVersion', 'lastPushed', 'slug'];

        requiredKeys.forEach(key => {
            if (!Object.prototype.hasOwnProperty.call(plugin, key)) {
                throw new Error(`Missing required key "${key}" in plugin at index ${index}`);
            }

            if (typeof plugin[key] !== 'string') {
                throw new Error(`Key "${key}" in plugin at index ${index} must be a string`);
            }
        });
    });
};

const PluginProvider = ({ children }) => {

    const [Data, dispatch] = useReducer(reducer, []);
    const [loaded, setLoaded] = useState(false);

    const fetchPlugins = () => {
        axios.get(PluginDataEndpoint, axiosOptions)
            .then(res => {
                if (res.status === 200 && res.data) {
    
                    try {
                        verifyPluginData(res.data);
                        dispatch({ type: 'INITIALIZE_PLUGINS', value: res.data });
                        setLoaded(true);
                    } catch (error) {
                        console.error(error);
                    }
                }
            })
            .catch(err => {
                console.log(err)
            })
    };

    useEffect(() => {
        fetchPlugins();
    }, []);

    const refreshPlugins = () => {
        setLoaded(false);
        fetchPlugins();
    }

    return (
        <PluginContext.Provider value={{ Data, dispatch, loaded, refreshPlugins }}>
            {children}
        </PluginContext.Provider>
    );
};

export default PluginProvider;
