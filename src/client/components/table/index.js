import React from 'react';

import PluginRow from './PluginRow';

function PluginTable() {

    // const plugins = [
    //     { name: 'Plugin 1', availableVersion: '1.0', currentVersion: '1.0', lastPushed: '2023-08-01', slug: 'plugin-1' },
    //     { name: 'Plugin 2', availableVersion: '1.0', currentVersion: '1.0', lastPushed: '2023-08-01', slug: 'plugin-2' },
    //     { name: 'Plugin 3', availableVersion: '1.0', currentVersion: '1.0', lastPushed: '2023-08-01', slug: 'plugin-3' },
    //     { name: 'Plugin 4', availableVersion: '1.0', currentVersion: '1.0', lastPushed: '2023-08-01', slug: 'plugin-4' },
    //     { name: 'Plugin 5', availableVersion: '1.0', currentVersion: '1.0', lastPushed: '2023-08-01', slug: 'plugin-5' },
    //     // More plugins...
    //   ]

    return (
        <div className="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg" >
            <table className="min-w-full divide-y divide-gray-300">
                <thead className="bg-gray-50">
                    <tr>
                        <th scope="col" className="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Plugin Name</th>
                        <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Available Version</th>
                        <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Current Version</th>
                        <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Last Pushed</th>
                        <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Composer Slug</th>
                        <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Action</th>

                    </tr>
                </thead>
                <tbody className="divide-y mb-12 divide-gray-200 bg-white overflow-visible">
                    {plugins.map((plugin) => <PluginRow key={plugin.slug} plugin={plugin} />)}
                </tbody>
            </table>
        </div>
    )
}


export default PluginTable;