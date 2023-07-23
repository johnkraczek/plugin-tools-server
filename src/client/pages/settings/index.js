import React, { useEffect } from 'react';
import { useTitle } from "../../contexts/titleContext"
import Box from '../../components/box';
import { useSettingsContext } from "../../contexts/settingContext"

const Settings = () => {

    const { formData, dispatch, saving, saveSelection } = useSettingsContext();
    const { setTitle } = useTitle();

    useEffect(() => {
        setTitle("YDTB Plugin Tools - Settings");
    }, [])

    return (
        <Box background='gray'>
            <div className="relative isolate overflow-hidden py-3 sm:py-32 lg:overflow-visible lg:px-0">
                <div className="space-y-10 divide-y divide-gray-900/10">
                    <div className="grid grid-cols-1 gap-x-8 gap-y-8 md:grid-cols-3">
                        <div className="px-4 sm:px-0">
                            <h2 className="text-base font-semibold leading-7 text-gray-900">Bitbucket Credentials</h2>
                            <p className="mt-1 text-sm leading-6 text-gray-600">
                                The Plugin Tools uses your Bitbucket credentials to access your repositories. Please Create an App Password with the following permissions: Repository: Read, Write for more information please visit <a href="https://support.atlassian.com/bitbucket-cloud/docs/create-an-app-password/" className="text-indigo-600 hover:text-indigo-500">Bitbucket Support: App Passwords</a>
                            </p>
                            <p className='border-t mt-2 pt-5 text-sm leading-6 text-gray-600'>
                                The Workspace Slug is the name of the workspace that contains the repositories you want to access. It is recommended that you have a dedicated workspace for your wordpress packages and plugins.
                            </p>
                        </div>

                        <div className="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl md:col-span-2">
                            <div className="px-4 py-6 sm:p-8">
                                <div className="grid max-w-2xl grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                                    <div className="sm:col-span-4">
                                        <label htmlFor="username" className="block text-sm font-medium leading-6 text-gray-900">
                                            Bitbucket Username
                                        </label>
                                        <div className="mt-2">
                                            <input
                                                id="username"
                                                name="username"
                                                type="text"
                                                placeholder="johndoe"
                                                className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 pl-3"
                                                value={formData.bitbucket_username}
                                                onChange={e => { dispatch({ type: "USERNAME", value: e.target.value }) }}
                                            />
                                        </div>
                                    </div>
                                    <div className="sm:col-span-4">
                                        <label htmlFor="appPass" className="block text-sm font-medium leading-6 text-gray-900">
                                            Bitbucket App Password
                                        </label>
                                        <div className="mt-2">
                                            <input
                                                id="appPass"
                                                name="appPass"
                                                type="password"
                                                placeholder="********"
                                                className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 pl-3"
                                                value={formData.bitbucket_password}
                                                onChange={e => { dispatch({ type: "PASSWORD", value: e.target.value }) }}
                                            />
                                        </div>
                                    </div>
                                    <div className="sm:col-span-4">
                                        <label htmlFor="appSlug" className="block text-sm font-medium leading-6 text-gray-900">
                                            Bitbucket Project Workspace
                                        </label>
                                        <div className="mt-2">
                                            <input
                                                id="appSlug"
                                                name="appSlug"
                                                type="text"
                                                placeholder="ydtb-plugin-tools"
                                                className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 pl-3"
                                                value={formData.bitbucket_workspace}
                                                onChange={e => { dispatch({ type: "WORKSPACE", value: e.target.value }) }}
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div className="flex items-center justify-end gap-x-6 border-t border-gray-900/10 px-4 py-4 sm:px-8">
                                <button className="btn btn-primary"
                                    onClick={() => { saveSelection() }}
                                    disabled={saving ? "disabled" : ""}
                                >
                                    {saving ? "Saving" : "Save Selection"}
                                    {saving ? <span className="loading loading-spinner"></span> : ""}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Box >
    )
}

export default Settings;