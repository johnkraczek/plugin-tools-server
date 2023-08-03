import React from 'react'
import SplitButton from './splitButton';

function handleClick(plugin, message) {
    console.log(plugin.name + " " + message);
}

function PluginRow({ plugin }) {
    // Initialize hooks and state here

    return (
        <tr className="overflow-visible">
            <td className="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">{plugin.name}</td>
            <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{plugin.availableVersion}</td>
            <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{plugin.currentVersion}</td>
            <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{plugin.lastPushed}</td>
            <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{plugin.slug}</td>
            <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-500"><SplitButton options={[
                { name: 'Active1', handler: () =>{handleClick(plugin, "Active")}, state: "active" },
                { name: 'Disabled', handler: () =>{handleClick(plugin, "Disable")}, state: "disabled" },
                { name: 'Pushing', handler: () =>{handleClick(plugin, "Pushing")}, state: "pushing" },
            ]} /></td>
        </tr>
    )
}

export default PluginRow;