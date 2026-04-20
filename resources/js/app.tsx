import React from 'react';
import ReactDOM from 'react-dom/client';
import './i18n';
import AppComponent from './AppComponent';
import '../css/app.css';

ReactDOM.createRoot(document.getElementById('root') as HTMLElement).render(
  <React.StrictMode>
    <AppComponent />
  </React.StrictMode>
);
