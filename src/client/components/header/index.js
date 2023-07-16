import React from 'react'
import Nav from '../nav'
import Notification from '../notification'
import Box from '../box'
import Title from '../title'

import { useTitle } from '../../contexts/titleContext';

const Header = ({nav}) => {

    const { title } = useTitle();
    return (
        <Box>
            <div className="relative flex h-20 justify-between">
                <div className="relative z-10 flex px-2 lg:px-0 items-center">
                    <div className="flex flex-shrink-0 items-center">
                        <img
                            className="block h-8 w-auto"
                            src="https://tailwindui.com/img/logos/mark.svg?color=red&shade=600"
                            alt="Your Company"
                        />
                    </div>
                    <Title>{title}</Title>
                </div>
                <Notification />
            </div>
            <Nav navigation={nav} />
        </Box>
    )
}


export default Header;