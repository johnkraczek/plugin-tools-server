import React from 'react'

import { ArrowPathIcon } from '@heroicons/react/24/solid';

import { usePluginContext } from '../../contexts/pluginContext';

const ReloadButton = () => {

    let { isProcessing, reloadData } = usePluginContext();

    function reload() {
        reloadData();
    }


    return (
        <>
            <div className="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                <button
                    type="button"
                    className="px-4 rounded-md bg-indigo-600 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 flex items-center justify-center"
                    onClick={() => { reload() }}
                    disabled={isProcessing}
                >
                    <ArrowPathIcon className={`h-5 w-5 ${isProcessing ? 'animate-spin' : null}`} />
                    <p className='ml-2'>
                    {isProcessing ? 'Processing' : 'Reload Data'}
                    </p>
                </button>
            </div>
        </>
    )
}

export default ReloadButton;