import React, { useState, useEffect, Fragment } from 'react';
import { Menu, Transition } from '@headlessui/react';
import { ChevronDownIcon, ArrowPathIcon } from '@heroicons/react/24/solid';

function validateOptions(options) {
    if (!Array.isArray(options)) {
      throw new Error(`Invalid prop: 'options' should be an array`);
    }
  
    options.forEach((option, index) => {
      if (typeof option !== 'object') {
        throw new Error(`Invalid prop: 'options[${index}]' should be an object`);
      }
  
      if (!option.hasOwnProperty('name') || typeof option.name !== 'string') {
        throw new Error(`Invalid prop: 'options[${index}].name' should be a string`);
      }
  
      if (!option.hasOwnProperty('handler') || typeof option.handler !== 'function') {
        throw new Error(`Invalid prop: 'options[${index}].handler' should be a function`);
      }
  
      if (!option.hasOwnProperty('state') || typeof option.state !== 'string') {
        throw new Error(`Invalid prop: 'options[${index}].state' should be a string`);
      }
  
      const validStates = ['active', 'disabled', 'pushing'];
      if (!validStates.includes(option.state)) {
        throw new Error(`Invalid prop: 'options[${index}].state' should be one of ${validStates.join(', ')}`);
      }
    });
  }

export default function SplitButton({ options , width = "w-32"  }) {
    const [selectedIndex, setSelectedIndex] = useState(0);

    useEffect(() => {
        validateOptions(options);
      }, [options]);
    
    return (
        <div className="inline-flex rounded-md shadow-sm">
            <button
                onClick={
                    options[selectedIndex].state == "active" ? options[selectedIndex].handler : () => {} 
                }
                className={`${width} inline-flex items-center justify-center px-4 py-2 border-r-1 bg-white text-sm font-medium text-gray-700 border border-gray-300 rounded-l-md focus:outline-none focus:ring-1 focus:ring-offset-1 focus:ring-grey-500 ${(options[selectedIndex].state == "pushing" || options[selectedIndex].state == "disabled") ? 'cursor-not-allowed' : ''}`}
                disabled={(options[selectedIndex].state == "pushing" || options[selectedIndex].state) == "disabled"}
            >
                {options[selectedIndex].name}
            </button>

            <Menu as="span" className="-ml-px relative block">
                <Menu.Button className={`inline-flex items-center px-2 py-2 bg-white text-sm font-medium text-gray-700 border border-l border-gray-300 rounded-r-md focus:outline-none focus:ring-1 focus:ring-offset-1 focus:ring-grey-500`}>
                    {options[selectedIndex].state == "pushing" ? <ArrowPathIcon className="h-5 w-5 animate-spin" /> : <ChevronDownIcon className="h-5 w-5" />}
                </Menu.Button>

                <Transition
                    as={Fragment}
                    enter="transition ease-out duration-100"
                    enterFrom="transform opacity-0 scale-95"
                    enterTo="transform opacity-100 scale-100"
                    leave="transition ease-in duration-75"
                    leaveFrom="transform opacity-100 scale-100"
                    leaveTo="transform opacity-0 scale-95"
                >
                    <Menu.Items className="origin-top-right text-left absolute right-0 mt-2 w-30 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-20 ">
                        <div className="py-1">
                            {options.map((option, index) => (
                                <Menu.Item key={index}>
                                    {({ active }) => (
                                        <div
                                            className={`${active ? 'bg-gray-100 text-gray-900' : 'text-gray-700'
                                                } block px-4 py-2 text-sm`}
                                            onClick={(e) => {
                                                setSelectedIndex(index);
                                            }}
                                        >
                                            {option.name}
                                        </div>
                                    )}
                                </Menu.Item>
                            ))}
                        </div>
                    </Menu.Items>
                </Transition>
            </Menu>
        </div>
    );
}
