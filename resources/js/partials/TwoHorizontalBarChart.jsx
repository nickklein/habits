import React from 'react'

function TwoHorizontalBarChart(props) {
    return (
        <div className="bg-gray-800 shadow-lg rounded-lg p-6 mt-4 relative">
            <h3 className={"text-xl font-semibold mb-3 "+props.color}>{props.title}</h3>
            <p className="text-white mb-5 text-xl font-semibold">{props.description}</p>
            <hr className="border-gray-600 mb-5"/>
            <div className="flex items-center mb-3">
                <span className="text-4xl text-white font-semibold mr-2">{props.barOne.number}</span>
                <span className="text-gray-400">{props.barOne.unit}</span>
            </div>
            <div className="relative h-6 w-full mb-3 rounded overflow-hidden">
                <div className={"absolute h-full "+props.bgColor} style={{width: `${props.barOne.width}%`}}></div>
                <span className="absolute left-2 text-white font-semibold">{props.barOne.barText}</span>
            </div>
            <div className="flex items-center mb-3">
                <span className="text-4xl text-white font-semibold mr-2">{props.barTwo.number}</span>
                <span className="text-gray-400">{props.barTwo.unit}</span>
            </div>
            <div className="relative h-6 w-full mb-3 rounded overflow-hidden">
                <div className={"absolute h-full bg-gray-400"} style={{width: `${props.barTwo.width}%`}}></div>
                <span className="absolute left-2 text-white font-semibold">{props.barTwo.barText}</span>
            </div>
        </div>
    )
}

export default TwoHorizontalBarChart