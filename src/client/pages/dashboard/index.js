import React, { useEffect } from 'react';
import { useTitle } from "../../contexts/titleContext"
import Box from '../../components/box';
import {
    CloudArrowUpIcon,
    ArchiveBoxArrowDownIcon,
    ClipboardDocumentListIcon,
    ArrowPathIcon,
    CloudArrowDownIcon
} from '@heroicons/react/20/solid'

const Dashboard = () => {

    const { setTitle } = useTitle();

    useEffect(() => {
        setTitle("YDTB Plugin Tools Server");
    }, [])

    return (
        <Box>
            <div className="relative isolate overflow-hidden py-12 sm:py-32 lg:overflow-visible lg:px-0">
                <div className="absolute inset-0 -z-10 overflow-hidden">
                    <svg
                        className="absolute left-[max(50%,25rem)] top-0 h-[64rem] w-[128rem] -translate-x-1/2 stroke-gray-200 [mask-image:radial-gradient(64rem_64rem_at_top,white,transparent)]"
                        aria-hidden="true"
                    >
                        <defs>
                            <pattern
                                id="e813992c-7d03-4cc4-a2bd-151760b470a0"
                                width={200}
                                height={200}
                                x="50%"
                                y={-1}
                                patternUnits="userSpaceOnUse"
                            >
                                <path d="M100 200V.5M.5 .5H200" fill="none" />
                            </pattern>
                        </defs>
                        <svg x="50%" y={-1} className="overflow-visible fill-gray-50">
                            <path
                                d="M-100.5 0h201v201h-201Z M699.5 0h201v201h-201Z M499.5 400h201v201h-201Z M-300.5 600h201v201h-201Z"
                                strokeWidth={0}
                            />
                        </svg>
                        <rect width="100%" height="100%" strokeWidth={0} fill="url(#e813992c-7d03-4cc4-a2bd-151760b470a0)" />
                    </svg>
                </div>
                <div className="mx-auto grid max-w-2xl grid-cols-1 gap-x-8 gap-y-16 lg:mx-0 lg:max-w-none lg:grid-cols-2 lg:items-start lg:gap-y-10">
                    <div className="lg:col-span-2 lg:col-start-1 lg:row-start-1 lg:mx-auto lg:grid lg:w-full lg:max-w-7xl lg:grid-cols-2 lg:gap-x-8 lg:px-8">
                        <div className="lg:pr-4">
                            <div className="lg:max-w-lg">
                                <p className="text-base font-semibold leading-7 text-indigo-600">Paid Plugins: Managed</p>
                                <h1 className="mt-2 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">A better plugin workflow</h1>
                                <p className="mt-6 text-xl leading-8 text-gray-700">
                                    It is frustrating to pay lots of money for a myriad of paid plugins. There's not one place that you can go to see all your plugins, and not one place you can go to find the updates. This is why I created <strong className="font-semibold text-gray-900">Plugin Tools.</strong>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div className="-ml-12 -mt-12 p-12 lg:sticky lg:top-4 lg:col-start-2 lg:row-span-2 lg:row-start-1 lg:overflow-hidden">
                        <img
                            className="w-[48rem] max-w-none rounded-xl bg-gray-900 shadow-xl ring-1 ring-gray-400/10 sm:w-[57rem]"
                            src="https://tailwindui.com/img/component-images/dark-project-app-screenshot.png"
                            alt=""
                        />
                    </div>
                    <div className="lg:col-span-2 lg:col-start-1 lg:row-start-2 lg:mx-auto lg:grid lg:w-full lg:max-w-7xl lg:grid-cols-2 lg:gap-x-8 lg:px-8">
                        <div className="lg:pr-4">
                            <div className="max-w-xl text-base leading-7 text-gray-700 lg:max-w-lg">
                                <p>
                                    If you are an agency owner like me you likely have lots of plugins installed on lots of sites. Managing the updates for all these plugins can be a nightmare. Here's how I do it:
                                </p>
                                <ul role="list" className="mt-8 space-y-8 text-gray-600">
                                    <li className="flex gap-x-3">
                                        <ClipboardDocumentListIcon className="mt-1 h-5 w-5 flex-none text-indigo-600" aria-hidden="true" />
                                        <span>
                                            <strong className="font-semibold text-gray-900">Plugin on client is licensed.</strong>
                                            This plugin checks for &lt;licensed&gt; plugins on this site. When an update is available, wordpress stores the URL from the plugin author to download the update into the database. This plugin can either immediately download the update zip, or just note the URL location for where to get that update.
                                        </span>
                                    </li>
                                    <li className="flex gap-x-3">
                                        <CloudArrowUpIcon className="mt-1 h-5 w-5 flex-none text-indigo-600" aria-hidden="true" />
                                        <span>
                                            <strong className="font-semibold text-gray-900">Plugin Info Pushed Centrally</strong> When there's an update availble This plugin calls a webhook on the Plugin Tools Server and sends along the info about where to get the update.
                                        </span>
                                    </li>
                                    <li className="flex gap-x-3">
                                        <ArrowPathIcon className="mt-1 h-5 w-5 flex-none text-indigo-600" aria-hidden="true" />
                                        <span>
                                            <strong className="font-semibold text-gray-900">Update Processing</strong> To efficiently store all of the plugins we automatically unzip the plugin and store it into a git repository for each plugin. This allows us to easily see the changes between versions, but more importantly efficiently stores all of the plugin versions.
                                        </span>
                                    </li>
                                    <li className="flex gap-x-3">
                                        <ArchiveBoxArrowDownIcon className="mt-1 h-5 w-5 flex-none text-indigo-600" aria-hidden="true" />
                                        <span>
                                            <strong className="font-semibold text-gray-900">Updates Available via Composer</strong> A PHP composer list is generated and includes all of the plugins with all versions that have been tracked. This allows you to easily include the latest version of your paid plugins in your composer file, and easily update with `composer update`.
                                        </span>
                                    </li>
                                    <li className="flex gap-x-3">
                                        <CloudArrowDownIcon className="mt-1 h-5 w-5 flex-none text-indigo-600" aria-hidden="true" />
                                        <span>
                                            <strong className="font-semibold text-gray-900">Updates Available Here </strong>
                                            You can also download the latest version of plugins that are not recieving updates on this site via the Store page. This page will show you all of the plugins that are available on the Plugin Tools server. Simply click the install button and the plugin will be downloaded to your site.
                                        </span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div className="bg-white px-6 py-32 lg:px-8">
                    <div className="mx-auto max-w-3xl text-base leading-7 text-gray-700">
                        <h1 className="mt-2 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Can you do that??</h1>
                        <p className="mt-8">
                            According to the GPLv2(3)+ licenses, The licence used by Wordpress, and most of its plugins. You are allowed to redistribute the plugin software. This means that you can download the latest version of a plugin from the author and put that on any site you want.</p>
                        <p className="mt-8"> If you check the fine print of what you are buying when buying GPLv2(3)+ licensed wordpress plugins, you are not buying the software, you are buying access to updates and support. This means that you can download the latest version of the plugin, and use it on any site you want. </p>
                        <p className="mt-8">But without actually having a license on that new site, you won't be able to ask for support if you have any issues. If your ok just figuring out the issues yourself, then this plugin solves the problem of delivering the latest version of the plugin to your site. This works as long as you are getting updates from at least a single licensed site.
                        </p>
                        <h2 className="mt-16 text-2xl font-bold tracking-tight text-gray-900">Should I Still Buy Licenses?</h2>
                        <p className="mt-6">
                            If you are a Client of YDTB or FunnelPress and we built your website, then <strong className="font-semibold text-gray-900">No</strong>, We purchased the licenses for you and you are free to use them on the site(s) we built for you. </p>
                            <p className="mt-6"> If you are <strong className="font-semibold text-gray-900">not</strong> a client of YDTB or FunnelPress, then:<br/>
                            <strong className="font-semibold text-gray-900">Yes.</strong> You should still buy licenses for all of your plugins. This is the only way to support the developers that are making these plugins. The sale of their plugins supports their families and allows them to continue to make great plugins. How many licenses you buy is up to you. Some plugins do limit features if their license is not activated. If you dont need those features, then you can get away with only buying a single license. additionally if you need support for a plugin, you will need to buy a license for that plugin on that site.
                        </p>
                    </div>
                </div>
            </div>
        </Box>
    )
}

export default Dashboard;