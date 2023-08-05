import React from 'react'
import SplitButton from './splitButton';

import ReactTimeAgo from 'react-time-ago'

import CopyToClipboardCell from './copyButton';

// right now this is just a placeholder function
function handleClick(plugin, message) {
    console.log(plugin.name + " " + message);
}

function PluginRow({ plugin }) {
    // Initialize hooks and state here

    let date = new Date(plugin.lastPushed);

    return (
        <tr className="overflow-visible">
            <td className="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">{plugin.name}</td>
            <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{plugin.currentVersion}</td>
            <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                <ReactTimeAgo date={date} locale="en-US" />
            </td>
            <CopyToClipboardCell slug={plugin.slug} />
            <td className="whitespace-nowrap text-right px-3 py-4 text-sm text-gray-500">
                <SplitButton options={[
                    { name: 'Active1', handler: () => { handleClick(plugin, "Active") }, state: "active" },
                    // { name: 'Disabled', handler: () => { handleClick(plugin, "Disable") }, state: "disabled" },
                    { name: 'Pushing', handler: () => { handleClick(plugin, "Pushing") }, state: "pushing" },
                ]} />
            </td>
        </tr>
    )
}

export default PluginRow;