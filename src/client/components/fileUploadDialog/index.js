import { Fragment, useState } from 'react';
import { Dialog, Transition } from '@headlessui/react';
import axios from 'axios';
import { ArrowPathIcon } from '@heroicons/react/24/outline'

import { usePluginContext } from '../../contexts/pluginContext';

const axiosOptions = {
    headers: { 'X-WP-Nonce': pts.nonce }
};

const PluginUploadEndpoint = pts.rest + 'upload-plugin';
const PluginCompleteEndpoint = pts.rest + 'complete-upload-plugin';

function ModalPopup() {
    const [isOpen, setIsOpen] = useState(false);
    const [file, setFile] = useState(null);
    const [modalStep, setModalStep] = useState(1);
    const [isProcessing, setIsProcessing] = useState(false);
    const [processedInfo, setProcessedInfo] = useState(null); // Added this state variable
    const [vendor, setVendor] = useState("");

    const { refreshPlugins } = usePluginContext();

    const handleFileChange = (e) => {
        setFile(e.target.files[0]);
    };

    const handleAdditionalProcessing = () => {
        if (!processedInfo) return;

        setIsProcessing(true);
        let completeData = { ...processedInfo }

        completeData.vendor = vendor;
        completeData.composerSlug = vendor + "/" + processedInfo.slug;

        // Sending the previously received processedInfo as the data
        axios.post(PluginCompleteEndpoint, { data: completeData }, axiosOptions)
            .then(res => {
                if (res.status === 200 && res.data.status === "success") {
                    //console.log('Additional processing successful:', res.data);
                    setModalStep(3);
                    setVendor("");
                    // You can add more logic here, e.g., update some state or show a notification
                } else {
                    console.log('Error during additional processing:', res.data.message);
                }
            })
            .catch(err => {
                console.error('Error during additional processing:', err);
            })
            .finally(() => {
                refreshPlugins();
                setIsProcessing(false);
            });
    };

    const handleSubmit = () => {
        if (!file) return;

        // Prepare form data for the file upload.
        const formData = new FormData();
        formData.append('file', file);

        setIsProcessing(true);

        axios.post(PluginUploadEndpoint, formData, axiosOptions)
            .then(res => {
                if (res.status === 200 && res.data.status === "success") {
                    //console.log('File uploaded and processed:', res.data);

                    // Store the processed information in local state.
                    setProcessedInfo(res.data.data);

                    // Transition to the second screen of the modal.
                    setModalStep(2);
                } else {
                    // Handle any errors or warnings.
                    console.log(res.data.message);
                }
            })
            .catch(err => {
                console.error('Error uploading the file:', err);
            })
            .finally(() => {
                setIsProcessing(false);
            });
    };

    const renderModalContent = () => {
        if (modalStep === 1) {
            return (
                <div className="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl">
                    <Dialog.Title
                        as="h3"
                        className="text-lg font-medium leading-6 text-gray-900"
                    >
                        Add New Plugin to Plugin Tools Server!
                    </Dialog.Title>

                    <div className="mt-2">
                        <p className="text-sm text-gray-500">
                            Please Select the plugin zip file to upload.
                        </p>

                        <div className="mt-4">
                            <label className="block text-sm font-medium text-gray-700">
                                Choose the file:
                            </label>
                            <input
                                type="file"
                                className="mt-2"
                                accept=".zip"
                                onChange={handleFileChange}
                            />
                        </div>
                    </div>

                    <div className="mt-4">
                        <button
                            type="button"
                            className="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-blue-500"
                            onClick={handleSubmit}
                            disabled={isProcessing}
                        >
                            {isProcessing ? (
                                <>
                                    <ArrowPathIcon className="animate-spin -ml-1 mr-3 h-5 w-5" />
                                    Processing
                                </>
                            ) : (
                                'Submit'
                            )}
                        </button>
                    </div>
                </div>
            );
        } else if (modalStep === 2) {
            return (
                <>
                    <div className="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl">
                        <Dialog.Title
                            as="h3"
                            className="text-lg font-medium leading-6 text-gray-900"
                        >
                            Here is the info on this plugin!
                        </Dialog.Title>

                        <div className="mt-2">
                            <div className="text-sm text-gray-500">
                                {processedInfo['newPlugin'] ?
                                    (
                                        <>
                                            <p>You have uploaded a new plugin thats not currently tracked on the server. Please enter the vendor name below.</p>
                                            <strong>Plugin Name:</strong> {processedInfo['name']}<br />
                                            <strong>Plugin Version:</strong> {processedInfo['newversion']}<br />
                                            <strong>Plugin Slug:</strong> {processedInfo['slug']}<br />
                                            <strong>File Size:</strong> {processedInfo['filesize']}<br />
                                            <strong>Composer Slug:</strong> {vendor == "" ? "start typing vendor..." : vendor + "/" + processedInfo['slug']}<br />
                                            <div className="mt-4">
                                                <label htmlFor="vendor" className="block text-sm font-medium text-gray-700">Vendor</label>
                                                <input
                                                    type="text"
                                                    id="vendor"
                                                    name="vendor"
                                                    placeholder="Enter vendor name"
                                                    value={vendor}
                                                    onChange={e => {
                                                        const filteredValue = e.target.value.replace(/[^a-zA-Z0-9-]/gi, '').toLowerCase();
                                                        setVendor(filteredValue);
                                                    }}
                                                    className="mt-1 p-2 w-full border rounded-md"
                                                />
                                            </div>
                                            <div className="mt-4 mr-2">
                                                <button
                                                    type="button"
                                                    className={`inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-blue-500 ${isProcessing || vendor.length < 3 ? 'opacity-50 cursor-not-allowed' : ''}`}
                                                    onClick={handleAdditionalProcessing}
                                                    disabled={isProcessing || vendor.length < 3}
                                                >
                                                    {isProcessing ? (
                                                        <>
                                                            <ArrowPathIcon className="animate-spin -ml-1 mr-3 h-5 w-5" />
                                                            Processing
                                                        </>
                                                    ) : (
                                                        'Submit Plugin Info'
                                                    )}
                                                </button>
                                            </div>
                                        </>
                                    ) : null}

                                {!processedInfo['newPlugin'] && processedInfo['versionCheck']['status'] ?
                                    (
                                        <>
                                            <p>You have uploaded a new version of an existing plugin. Please confirm the details below.</p>
                                            <strong>Plugin Name:</strong> {processedInfo['name']}<br />
                                            <strong>Plugin Version:</strong> {processedInfo['newversion']}<br />
                                            <strong>Composer Slug:</strong> {processedInfo['slug']}<br />
                                            <strong>File Size:</strong> {processedInfo['filesize']}<br />
                                            <br />
                                            <p> If you are happy with this info then click continue to finish processing the plugin.</p>
                                            <div className="mt-4 mr-2">
                                                <button
                                                    type="button"
                                                    className={`inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-blue-500 ${isProcessing ? 'opacity-50 cursor-not-allowed' : ''}`}
                                                    onClick={handleAdditionalProcessing}
                                                    disabled={isProcessing}
                                                >
                                                    {isProcessing ? (
                                                        <>
                                                            <ArrowPathIcon className="animate-spin -ml-1 mr-3 h-5 w-5" />
                                                            Processing
                                                        </>
                                                    ) : (
                                                        'Continue Plugin Update'
                                                    )}
                                                </button>
                                            </div>
                                        </>
                                    ) : null
                                }
                                {
                                    (!processedInfo['newPlugin'] && !processedInfo['versionCheck']['status']) ? (
                                        <>
                                            <p>You have uploaded an older version of an existing plugin.</p>
                                            <strong>Plugin Name:</strong> {processedInfo['name']}<br />
                                            <strong>Plugin Uploaded Version:</strong> {processedInfo['newversion']}<br />
                                            <strong>Plugin Current Version:</strong> {processedInfo['versionCheck']['currentVersion']}<br />
                                            <strong>Folder Slug:</strong> {processedInfo['slug']}<br />
                                            <p>You will not be able to add this plugin automatically. As this can be a complicated process to rewrite the Git History. Try updating with a newer plugin version. </p>
                                        </>
                                    ) : null
                                }
                                <div className="mt-4">
                                    <button
                                        type="button"
                                        className="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-blue-500"
                                        onClick={() => {
                                            setIsOpen(false)
                                            setTimeout(() => {
                                                setModalStep(1);
                                                setVendor("");
                                                refreshPlugins();
                                            }, 1000);
                                        }
                                        }
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </>
            );

        } else if (modalStep === 3) {
            return (
                <>
                    <div className="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl">
                        <Dialog.Title
                            as="h3"
                            className="text-lg font-medium leading-6 text-gray-900"
                        >
                            Done Updating/Adding The Plugin!
                        </Dialog.Title>
                        <div className="mt-2">
                            <div className="text-sm text-gray-500">
                                <p>Plugin has been added to the Repository.</p>
                            </div>
                        </div>
                        <div className="mt-4">
                            <button
                                type="button"
                                className="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-blue-500"
                                onClick={() => {
                                    setIsOpen(false)
                                    setTimeout(() => {
                                        setModalStep(1);
                                        setVendor("");
                                    }, 1000);
                                }
                                }
                            >
                                OK!
                            </button>
                        </div>
                    </div>
                </>
            )
        }
    };

    return (
        <>
            <div className="mt-4 w-25 sm:ml-2 sm:mt-0 sm:flex-none">
                <button
                    onClick={() => setIsOpen(true)}
                    type="button"
                    className="px-4 block rounded-md bg-indigo-600 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                >
                    Add Plugin
                </button>
            </div>

            <Transition appear show={isOpen} as={Fragment}>
                <Dialog
                    as="div"
                    className="fixed inset-0 z-10 overflow-y-auto"
                    onClose={() => setIsOpen(false)}
                >
                    <div className="min-h-screen px-4 text-center">
                        <Dialog.Overlay className="fixed inset-0 bg-gray-500 bg-opacity-75" />

                        <span
                            className="inline-block h-screen align-middle"
                            aria-hidden="true"
                        >
                            &#8203;
                        </span>

                        {renderModalContent()}
                    </div>
                </Dialog>
            </Transition>
        </>
    );
}

export default ModalPopup;
