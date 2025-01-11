import { Link } from '@inertiajs/react';
import React from 'react'
import { getTextColor, getBackgroundColor } from '@/Helpers/Colors'; // import helper functions

function Card(props) {
    let color = getTextColor(props.color);
    let bgColor = getBackgroundColor(props.color);
    
    return (
        <div className={"bg-gray-800 shadow-lg rounded-lg p-6 mt-4 " + props.className}>
            {props.children}
        </div>
    )
}

Card.defaultProps = {
    className: '',
}

export default Card