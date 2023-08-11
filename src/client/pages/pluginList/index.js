import React, { useEffect } from 'react';
import { useTitle } from "../../contexts/titleContext"
import Box from '../../components/box';
import PluginTable from '../../components/table/index.js';
import ModalPopup from '../../components/fileUploadDialog/index.js';
import ReloadButton from '../../components/reloadButton';

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
                    <ReloadButton />
                    <ModalPopup />
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