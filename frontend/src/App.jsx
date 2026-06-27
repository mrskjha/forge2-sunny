import { useState } from 'react'
import './index.css'

function App() {
  const [count, setCount] = useState(0)

  return (
    <div className="flex flex-col items-center justify-center min-h-screen bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-900 dark:to-gray-800">
      <div className="bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-12 max-w-md w-full text-center border border-gray-200 dark:border-gray-700">
        <h1 className="text-5xl font-bold mb-2 text-gray-900 dark:text-white">PulseDesk</h1>
        <h2 className="text-xl text-gray-600 dark:text-gray-400 mb-8">Support Ticket System</h2>
        <button className="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-8 rounded-lg shadow-lg transition-all duration-200 hover:shadow-xl transform hover:-translate-y-0.5">
          New Ticket
        </button>
      </div>
    </div>
  )
}

export default App