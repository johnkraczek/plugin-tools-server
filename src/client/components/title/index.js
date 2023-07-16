import React from "react";

const Title = ({children}) => {

    return (
        <div className="min-w-0 flex-1 items-center">
            <h2 className="ml-10 text-2xl font-bold text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                {children}
            </h2>
        </div>
    )
}

export default Title;