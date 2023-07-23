import React from "react";

const Box = ({ children, background = "white" }) => {
    let boxClasses = "mx-5 my-9 px-2 sm:px-4 lg:divide-y lg:divide-gray-300 lg:px-8 rounded-lg shadow min-h-[50px] ";
    if (background === "white") {
        boxClasses += "bg-white ";
    } else if (background === "gray") {
        boxClasses += "bg-gray-100 ";
    }

    return (
        <div className={boxClasses}>
            {children }
        </div>
    )
}

export default Box;