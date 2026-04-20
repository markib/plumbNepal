import React from 'react';
import { useTranslation } from 'react-i18next';
import { MapContainer, TileLayer, Marker, useMapEvents } from 'react-leaflet';
import type { LatLngExpression, LeafletMouseEvent } from 'leaflet';
import type { BookingFormValues } from '../types';

interface MapPinAddressProps {
  value: BookingFormValues;
  onChange: (value: BookingFormValues) => void;
}

const LocationMarker: React.FC<MapPinAddressProps> = ({ value, onChange }) => {
  const position: LatLngExpression = [value.latitude, value.longitude];

  useMapEvents({
    click(e: LeafletMouseEvent) {
      onChange({ ...value, latitude: e.latlng.lat, longitude: e.latlng.lng });
    },
  });

  return <Marker position={position} />;
};

const MapPinAddress: React.FC<MapPinAddressProps> = ({ value, onChange }) => {
  const { t } = useTranslation();
  const position: LatLngExpression = [value.latitude, value.longitude];

  return (
    <div className="space-y-3 rounded-lg border border-slate-200 bg-slate-50 p-4">
      <p className="text-sm text-slate-600">{t('pinOnMapInstruction')}</p>
      <div className="h-80 w-full overflow-hidden rounded-lg border border-slate-300">
        <MapContainer center={position} zoom={13} className="h-full w-full">
          <TileLayer url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" />
          <LocationMarker value={value} onChange={onChange} />
        </MapContainer>
      </div>
      <p className="text-sm text-slate-700">
        {t('currentCoordinates')}: {value.latitude.toFixed(5)}, {value.longitude.toFixed(5)}
      </p>
    </div>
  );
};

export default MapPinAddress;
