import React from 'react';

import PluginRow from './PluginRow';

import { usePluginContext } from "../../contexts/pluginContext";

function PluginTable() {

    const { PluginData } = usePluginContext();
    

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
                    {PluginData.map((plugin) => <PluginRow key={plugin.slug} plugin={plugin} />)}
                </tbody>
            </table>
        </div>
    )
}


export default PluginTable;