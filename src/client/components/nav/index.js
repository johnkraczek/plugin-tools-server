import React from 'react';
import classNames from 'classnames';

import {useLocation} from 'react-router-dom';

const Nav = ({navigation}) => {
    const  {pathname}  = useLocation();

    return (
        <div className="flex space-x-8 py-2 h-14">
            {navigation.map((item) => (
                <a
                    key={item.name}
                    href={item.href}
                    className={classNames(
                        'inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium',
                        {'border-indigo-500 text-gray-900': '#'+pathname === item.href },
                        {'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700': '#'+pathname !== item.href}
                    )}
                    aria-current={item.current ? 'page' : undefined}
                >
                    {item.name}
                </a>
            ))}
        </div>
    )
}

export default Nav;
