import React from "react";

const Box = ({ children }) => {

    return (
        <div className="mx-5 my-9 px-2 sm:px-4 lg:divide-y lg:divide-gray-200 lg:px-8 rounded-lg bg-white shadow min-h-[50px]">
            {children }
        </div>
    )

}

export default Box;