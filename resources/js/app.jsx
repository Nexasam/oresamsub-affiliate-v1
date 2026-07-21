import '../css/app.css'
import { createInertiaApp } from '@inertiajs/react'
import { createRoot } from 'react-dom/client'
import { route } from 'ziggy-js'
import LoaderOverlay from '@/Components/LoaderOverlay.jsx'
import PwaInstallPopup from "@/Components/PwaInstallPopup";

const getInitialPage = () => {
  const pageScript = document.querySelector(
    'script[data-page="app"][type="application/json"]',
  )
  const legacyPage = document.getElementById('app')?.dataset.page
  const serializedPage = pageScript?.textContent || legacyPage

  if (!serializedPage) {
    throw new Error(
      'Unable to start Inertia: the server response does not contain page data.',
    )
  }

  return JSON.parse(serializedPage)
}

const pages = import.meta.glob('./Pages/**/*.jsx', { eager: true })

if (import.meta.env.DEV && 'serviceWorker' in navigator) {
  navigator.serviceWorker.getRegistrations().then(registrations => {
    registrations.forEach(registration => registration.unregister())
  })
}

createInertiaApp({
  // Passing the page explicitly supports both Inertia 3's JSON script and the
  // legacy data-page attribute during deployments where cached views may lag.
  page: getInitialPage(),
  resolve: name => {
    const page = pages[`./Pages/${name}.jsx`]

    if (!page) {
      throw new Error(`Unknown Inertia page: ${name}`)
    }

    return page
  },
  setup({ el, App, props }) {
    createRoot(el).render(
      <>
        <App {...props} />
        <LoaderOverlay />
        {/* <PwaInstallPopup /> */}

      </>
    )
  },
})

window.route = (name, params, absolute) => route(name, params, absolute, Ziggy);
