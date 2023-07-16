import React from 'react'

const SplitButton = () => {


    return (
        <div className="join">
            <button className="btn join-item btn-xs">Push</button>
            <button className="btn join-item btn-xs"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-6 h-6">
                <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
            </svg>
            </button>
        </div>
    )
}

export default SplitButton;