import React from 'react';
import { Routes, Route } from "react-router-dom";

import Header from './components/header';

import Dashboard from './pages/dashboard';
import Settings from './pages/settings';
import PluginList from './pages/pluginList';

const navigation = [
  { name: 'Dashboard', href: '#/'},
  { name: 'Plugin List', href: '#/pluginList'},
  { name: 'Settings', href: '#/settings'}
]

const App = () => {

  return (
    <div>
      <Header nav={navigation} />
      <Routes>
        <Route path="/" element={<Dashboard />} />
        <Route path="/pluginList" element={<PluginList />} />
        <Route path="/settings" element={<Settings />} />
      </Routes>
    </div>
  )
}

export default App;