import { useState } from 'react';
import clipboardCopy from 'clipboard-copy';
import { DocumentDuplicateIcon } from '@heroicons/react/24/outline';

const CopyToClipboardCell = ({ slug }) => {
    const [isHovering, setIsHovering] = useState(false);
    const [isCopied, setIsCopied] = useState(false);

    const handleMouseEnter = () => {
        setIsHovering(true);
    };

    const handleMouseLeave = () => {
        setTimeout(() => {
            setIsHovering(false);
        }, 100)
    };

    const handleCopy = () => {
        clipboardCopy(slug);
        setIsCopied(true);
        setTimeout(() => {
            setIsCopied(false);
        }, 1000);
    };

    return (
        <td
            className="whitespace-nowrap px-3 py-4 text-sm text-gray-500 flex justify-right items-center"
            onMouseEnter={handleMouseEnter}
            onMouseLeave={handleMouseLeave}
        >
            <span>{slug}</span>
            <button
                className={`transition-opacity duration-500 text-left pl-3 py-4 ${isHovering ? '' : 'invisible'}`}
                onClick={handleCopy}
            >
                <DocumentDuplicateIcon className="h-5 w-5" />
            </button>

            <span className={`text-xs text-green-500 pl-3 transition-opacity duration-500 ${isCopied ? '' : 'invisible'}`}>Copied!</span>
        </td>
    );
};

export default CopyToClipboardCell;
