import React, { createContext, useContext, useEffect, useReducer, useState } from "react";
import axios from "axios";

import { pluginsReducer } from "./reducer";

const axiosOptions = {
    headers: { 'X-WP-Nonce': pts.nonce }
};
const PluginDataEndpoint = pts.rest + 'plugins';
const PluginRefreshEndpoint = pts.rest + 'refresh';

const PluginContext = createContext();
export const usePluginContext = () => useContext(PluginContext);

const verifyPluginData = (plugins) => {
    if (!Array.isArray(plugins)) {
        throw new Error("Plugins data must be an array");
    }

    if (plugins.length === 0) {
        return true;
    }

    plugins.forEach((plugin, index) => {
        if (typeof plugin !== 'object') {
            throw new Error(`Plugin at index ${index} must be an object`);
        }

        const requiredKeys = ['name', 'currentVersion', 'lastPushed', 'slug'];

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

    const [PluginData, dispatch] = useReducer(pluginsReducer, []);
    const [loaded, setLoaded] = useState(false);
    const [isProcessing, setIsProcessing] = useState(false);

    const fetchPlugins = (endpoint) => {
        setIsProcessing(true);
        setLoaded(false);
        //console.log("fetching data from: " + endpoint);
        axios.get(endpoint, axiosOptions)
            .then(res => {
                if (res.status === 200 && res.data) {
                    try {
                        //console.log('successfully fetched data from endpoint '+ endpoint)
                        // console.log("the data is: " + res.data)
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
            .finally(() => {
                //console.log("finally")
                setIsProcessing(false);
            });
    };

    useEffect(() => {
        fetchPlugins(PluginDataEndpoint);
    }, []);

    const refreshPlugins = () => {
        fetchPlugins(PluginDataEndpoint);
    }

    const reloadData = () => {
        fetchPlugins(PluginRefreshEndpoint);
    }


    return (
        <PluginContext.Provider value={{ PluginData, dispatch, loaded, refreshPlugins, reloadData, isProcessing }}>
            {children}
        </PluginContext.Provider>
    );
};

export default PluginProvider;
