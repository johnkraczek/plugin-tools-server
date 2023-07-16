import React from 'react'

import { useLayoutEffect, useRef, useState } from 'react'
import SplitButton from '../splitButton'

const plugins = [
  {
    name: 'Elementor',
    availableVersion: '-',
    currentVersion: '1.2.3',
    lastPushedVersion: '1.2.3',
    lastPushed: '2021-09-01',
    composerSlug: 'elementor/elementor',
    downloadLocal: true,
    key: '1',
  },
  {
    name: 'Ultimate Elementor',
    availableVersion: '-',
    currentVersion: '1.2.3',
    lastPushedVersion: '1.2.3',
    lastPushed: '2021-09-01',
    composerSlug: 'Ultimate/elementor',
    downloadLocal: true,
    key: '2',
  }
  // More people...
]

function classNames(...classes) {
  return classes.filter(Boolean).join(' ')
}

const Table = () => {
  const checkbox = useRef()
  const [checked, setChecked] = useState(false)
  const [indeterminate, setIndeterminate] = useState(false)
  const [selectedPlugin, setSelectedPeople] = useState([])

  useLayoutEffect(() => {
    const isIndeterminate = selectedPlugin.length > 0 && selectedPlugin.length < plugins.length
    setChecked(selectedPlugin.length === plugins.length)
    setIndeterminate(isIndeterminate)
    checkbox.current.indeterminate = isIndeterminate
  }, [selectedPlugin])

  function toggleAll() {
    setSelectedPeople(checked || indeterminate ? [] : plugins)
    setChecked(!checked && !indeterminate)
    setIndeterminate(false)
  }

  return (
    <div className="px-4 sm:px-6 lg:px-8">
      <div className="mt-8 flow-root">
        <div className="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
          <div className="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
            <div className="relative">
              {selectedPlugin.length > 0 && (
                <div className="absolute left-14 top-0 flex h-12 items-center space-x-3 bg-white sm:left-12">
                  <button
                    type="button"
                    className="inline-flex items-center rounded bg-white px-2 py-1 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-white"
                  >
                    Bulk edit
                  </button>
                  <button
                    type="button"
                    className="inline-flex items-center rounded bg-white px-2 py-1 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-white"
                  >
                    Delete all
                  </button>
                </div>
              )}
              <table className="min-w-full table-fixed divide-y divide-gray-300">
                <thead>
                  <tr>
                    <th scope="col" className="relative px-7 sm:w-12 sm:px-6">
                      <input
                        type="checkbox"
                        className="absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                        ref={checkbox}
                        checked={checked}
                        onChange={toggleAll}
                      />
                    </th>
                    <th scope="col" className="min-w-[12rem] py-3.5 pr-3 text-left text-sm font-semibold text-gray-900">
                      Plugin Name
                    </th>
                    <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                      Available Version
                    </th>
                    <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                      Current Version
                    </th>
                    <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                      Last Pushed Version
                    </th>
                    <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                      Last Pushed
                    </th>
                    <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                      Composer Slug
                    </th>
                    <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                      Download Local
                    </th>
                    <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                      Action
                    </th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-200 bg-white">
                  {plugins.map((plugin) => (
                    <tr key={plugin.key} className={selectedPlugin.includes(plugin) ? 'bg-gray-50' : undefined}>
                      <td className="relative px-7 sm:w-12 sm:px-6">
                        {selectedPlugin.includes(plugin) && (
                          <div className="absolute inset-y-0 left-0 w-0.5 bg-indigo-600" />
                        )}
                        <input
                          type="checkbox"
                          className="absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                          value={plugin.email}
                          checked={selectedPlugin.includes(plugin)}
                          onChange={(e) =>
                            setSelectedPeople(
                              e.target.checked
                                ? [...selectedPlugin, plugin]
                                : selectedPlugin.filter((p) => p !== plugin)
                            )
                          }
                        />
                      </td>
                      <td
                        className={classNames(
                          'whitespace-nowrap py-4 pr-3 text-sm font-medium',
                          selectedPlugin.includes(plugin) ? 'text-indigo-600' : 'text-gray-900'
                        )}
                      >
                        {plugin.name}
                      </td>
                      <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{plugin.availableVersion}</td>
                      <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{plugin.currentVersion}</td>
                      <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{plugin.lastPushedVersion}</td>
                      <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{plugin.lastPushed}</td>
                      <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{plugin.composerSlug}</td>
                      <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                      <input
                          type="checkbox"
                          className=" mt-2 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                          value={plugin.email}
                          checked={selectedPlugin.includes(plugin)}
                          onChange={(e) =>
                            setSelectedPeople(
                              e.target.checked
                                ? [...selectedPlugin, plugin]
                                : selectedPlugin.filter((p) => p !== plugin)
                            )
                          }
                        />
                      </td>
                      <td className="whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-3">
                          <SplitButton />
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

export default Table