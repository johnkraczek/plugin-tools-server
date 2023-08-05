import React, { useEffect } from 'react';
import { useTitle } from "../../contexts/titleContext"
import Box from '../../components/box';
import PluginTable from '../../components/table/index.js';

const PluginList = () => {

    const { setTitle } = useTitle();

    useEffect(() => {
        setTitle("YDTB Plugin Tools - Plugin List");
    }, [])

    return (
        <Box>
            <div className="px-4 sm:px-6 lg:px-8 py-6">
                <div className="sm:flex sm:items-center">
                    <div className="sm:flex-auto">
                        <h1 className="text-base font-semibold leading-6 text-gray-900">Plugins Tracked</h1>
                        <p className="mt-2 text-sm text-gray-700">
                            This is a list of all plugins tracked by YDTB. You can add a new plugin by clicking the button.
                        </p>
                    </div>
                    <div className="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                        <button
                            type="button"
                            className="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                        >
                            Add Plugin
                        </button>
                    </div>
                </div>
            </div>
            <PluginTable />
            <div className='mb-10 border-none'>
                &nbsp;
            </div>

        </Box >
    )
}

export default PluginList;