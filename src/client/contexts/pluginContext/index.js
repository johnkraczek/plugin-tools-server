import React, { createContext, useContext, useEffect, useReducer, useState } from "react";
import axios from "axios";
import { reducer, initialList } from './reducer.js';
import transform from "./transform.js";
import Swal from 'sweetalert2'

const axiosOptions = {
  headers: { 'X-WP-Nonce': ydtb.nonce }
};

//setup our routesx
const pluginInfoEndpoint = ydtb.rest + '/plugin_info';
const saveDataEndpoint = ydtb.rest + '/save_plugin_info';
const pluginPushEndpoint = ydtb.rest + '/push_plugin_updates';

const PluginContext = createContext();
export const usePlugins = () => useContext(PluginContext);

const PluginProvider = ({ children }) => {
  const [loaded, setLoaded] = useState(false);
  const [saving, setSaving] = useState(false);
  const [pushing, setPushing] = useState({ active: false, plugin: '' });
  const [plugins, dispatch] = useReducer(reducer, initialList);

  const getPluginData = async () => {
    await axios.get(pluginInfoEndpoint, axiosOptions)
      .then(response => {
        if (response.status == 200) {
          let data = transform(response.data);
          dispatch({ type: "REPLACE", data });
          setLoaded(true);
        }
      })
      .catch(err => {
        console.log('there was an error', err);
      })
  }
  useEffect(() => {
    getPluginData();
  }, [])

  const saveSelection = async () => {
    setSaving(true);
    let payload = {};
    plugins.forEach(plugin => {
      let data = {
        checked: plugin.checked,
        composer: plugin.composerSlug
      }
      payload[plugin.slug] = data;
    });

    await axios.post(saveDataEndpoint, payload, axiosOptions)
      .then(res => {

      })
      .catch(err => {
        console.log(err)
      })
      .finally(() => {
        setTimeout(() => {
          setSaving(false)
        }, 500);
      })
  }

  const PushPlugin = (name, local = false) => {
    let payload;

    if (!name) {
      payload = { "name": '' };
      setPushing({ active: true, plugin: 'all' });
    } else {
      if (local) {
        payload = { "name": name, local: true };
      } else {
        payload = { "name": name }
      }

      setPushing({ active: true, plugin: name });
    }

    // check if one of the plugins named is in a blurred state, we would need to prompt someone to save 

    if (isBlurred()) {
      Swal.fire({
        title: 'Unsaved Changes',
        text: 'There are unsaved changes on the page. Would you like to save before pushing?',
        showDenyButton: true,
        showCancelButton: true,
        denyButtonText: 'No',
        confirmButtonText: 'Yes',
        icon: 'warning',
      })
        .then(result => {
          if (result.isConfirmed) {
            saveSelection()
              .then(() => getPluginData())
              .then(() => axiosPush(payload));
          } else {
            setPushing({ active: false, plugin: '' });
          }
        })
    } else {
      axiosPush(payload);
    }
  }

  const axiosPush = (payload) => {
    axios.post(pluginPushEndpoint, payload, axiosOptions)
      .then(res => {
        setPushing({ active: false, plugin: '' });
        toastAlert(res);
      })
      .catch(err => {
        console.log(err);
      })
      .finally(() => {
        getPluginData();
      })
  }

  const toastAlert = (response) => {

    const Toast = Swal.mixin({
      toast: true,
      position: 'bottom-right',
      iconColor: 'white',
      showConfirmButton: false,
      timer: 5000,
      timerProgressBar: true
    })

    let pluginResponseList = response.data.results;

    pluginResponseList.forEach(pluginResponse => {
      let responseData = pluginResponse.value;
      if (responseData.success) {
        if (responseData.state == "Tag-Exists") {
          Toast.fire({
            icon: 'success',
            title: `${responseData.slug} - ${responseData.new_tag} already exists.`,
            background: '#a5dc86'
          })
        }
        if (responseData.state == "Success") {
          Toast.fire({
            icon: 'success',
            title: `${responseData.slug} - ${responseData.new_tag} was successfully pushed.`,
            background: '#a5dc86'
          })
        }
      } else {
        Toast.fire({
          icon: 'error',
          title: `There was an error pushing ${responseData.slug} - ${responseData.new_tag}`,
          background: '#f27474'
        })
      }
    })
  }

  const isBlurred = () => {
    let blurred = false;
    plugins.forEach(plugin => {
      if (plugin.blurred) {
        blurred = true;
      }
    })
    return blurred;
  }

  return (
    <PluginContext.Provider value={{ plugins, dispatch, loaded, saveSelection, saving, PushPlugin, pushing }}>
      {children}
    </PluginContext.Provider>
  );
};

export default PluginProvider;
