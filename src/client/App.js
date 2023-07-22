import React from 'react';
import { Routes, Route } from "react-router-dom";

import Header from './components/header';

import Dashboard from './pages/dashboard';
import Whitelist from './pages/whitelist'
import Store from './pages/store';
import Settings from './pages/settings';

const navigation = [
  { name: 'Dashboard', href: '#/'},
  { name: 'Settings', href: '#/settings'}
]

const App = () => {

  return (
    <div>
      <Header nav={navigation} />
      <Routes>
        <Route path="/" element={<Dashboard />} />
        <Route path="/settings" element={<Settings />} />
      </Routes>
    </div>
  )
}

export default App;