import React from 'react';
import { createRoot } from 'react-dom';
import { HashRouter } from "react-router-dom";

import Context from './contexts';

import App from './App';

/** 
 * This is the entrypoint for the admin page. Because wordpress styles many times 
 * mess with the react styles we need to use shadow dom to isolate the styles.
 * We pass the location of the stylesheet through the ydtb object. This is populated
 * with the wp_localize_script in the AdminPageProvider.php file.
*/

const container = document.querySelector('#ydtb-plugin-tools-server-root');
const shadowContainer = container.attachShadow({ mode: 'open' });
const shadowRootElement = document.createElement('div');
const shadowStyleElement = document.createElement('link');
shadowStyleElement.rel = 'stylesheet';
shadowStyleElement.type = 'text/css';
shadowStyleElement.href = `${ydtb.root}/dist/css/client.css`;

shadowContainer.appendChild(shadowStyleElement);
shadowContainer.appendChild(shadowRootElement);

const root = createRoot(shadowRootElement);
root.render(
    <HashRouter>
        <Context>
            <App />
        </Context>
    </HashRouter>
)
