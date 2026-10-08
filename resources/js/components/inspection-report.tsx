/**
 * Onderdeel: Herbruikbare weergave van keuringsdatum, checklist en opmerkingen voor verkopers en kopers.
 * Eisen: FE-05, FE-07, FE-11, RV-06, TE-06.
 * Ontwerp: T-09 (keuringsopmerkingen), T-12 (checklist), T-18 (winkelweergave).
 * Bouw: T-10 (opmerkingen), T-13 (checklist), T-19 (winkel), T-31 (herbruikbare onderdelen).
 * Geplande controle: T-11, T-14, T-20, T-32.
 */

export const inspectionChecks = [
    ['works', 'Apparaat werkt volledig'],
    ['accessories_work', 'Accessoires werken goed'],
    ['presentable', 'Apparaat is in vertoonbare staat'],
    ['plugs_present', 'Alle benodigde stekkers zijn aanwezig'],
    ['ports_work', 'Aansluitingen werken goed'],
    ['reset_done', 'Fabrieksreset is uitgevoerd'],
    ['screen_work', 'Scherm werkt goed'],
    ['battery_work', 'Batterij werkt goed'],
] as const;

export type Inspection = Partial<Record<(typeof inspectionChecks)[number][0], boolean | 0 | 1 | '0' | '1'>> & {
    notes?: string | null;
    battery_percentage?: number | string | null;
    video_port?: string | null;
    port_types?: string | null;
};

export default function InspectionReport({
    inspection,
    deviceType,
    status,
    inspectedAt,
}: {
    inspection: Inspection | null;
    deviceType: string;
    status: string;
    inspectedAt: string | null;
}) {
    if (!inspection) {
        return <p className="mt-3 text-sm text-slate-500">Er zijn nog geen keuringsresultaten beschikbaar voor dit apparaat.</p>;
    }

    const visibleChecks = inspectionChecks.filter(([key]) => deviceType !== 'console' || (key !== 'screen_work' && key !== 'battery_work'));

    return (
        <div className="mt-4 space-y-4 text-sm">
            <p>
                <span className="font-semibold">Status: </span>
                {status}
            </p>
            {inspectedAt && (
                <p className="text-slate-600">
                    Gekeurd op{' '}
                    <time dateTime={inspectedAt}>{new Date(inspectedAt).toLocaleDateString('nl-NL', { timeZone: 'Europe/Amsterdam' })}</time>
                </p>
            )}
            <dl className="grid gap-3 sm:grid-cols-2">
                {visibleChecks.map(([key, label]) => {
                    const result = inspection[key];
                    const passed = result === true || result === 1 || result === '1';
                    return (
                        <div key={key} className="rounded-lg border border-slate-200 p-3">
                            <dt className="font-medium">{label}</dt>
                            <dd className={'mt-1 ' + (result == null ? 'text-slate-500' : passed ? 'text-emerald-700' : 'text-red-700')}>
                                {result == null ? 'Niet vastgelegd' : passed ? 'Geslaagd' : 'Niet geslaagd'}
                            </dd>
                        </div>
                    );
                })}
                {deviceType !== 'console' && (
                    <div>
                        <dt className="font-medium">Batterijconditie</dt>
                        <dd>{inspection.battery_percentage != null ? inspection.battery_percentage + '%' : 'Niet vastgelegd'}</dd>
                    </div>
                )}
                {deviceType === 'console' && (
                    <div>
                        <dt className="font-medium">Type videopoort</dt>
                        <dd className="break-words whitespace-pre-wrap">{inspection.video_port || 'Niet vastgelegd'}</dd>
                    </div>
                )}
                {deviceType === 'laptops' && (
                    <div>
                        <dt className="font-medium">Typen aansluitingen</dt>
                        <dd className="break-words whitespace-pre-wrap">{inspection.port_types || 'Niet vastgelegd'}</dd>
                    </div>
                )}
            </dl>
            <div className="rounded-lg bg-slate-50 p-4">
                <h3 className="font-semibold">Opmerkingen van de keurmeester</h3>
                <p className="mt-2 break-words whitespace-pre-wrap">{inspection.notes || 'Geen opmerkingen vastgelegd.'}</p>
            </div>
        </div>
    );
}
