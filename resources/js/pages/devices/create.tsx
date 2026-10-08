/**
 * Onderdeel: Aanmeldformulier voor type, merk, model, serienummer, conditie, accessoires, vraagprijs en foto's.
 * Eisen: FE-02, FE-03, RV-04, RV-06, RV-07, TE-04.
 * Ontwerp: T-06 (apparaat aanmelden en gegevens).
 * Bouw: T-07 (apparaat aanmelden), T-29 (invoercontrole).
 * Geplande controle: T-08, T-30.
 */

import InputError from '@/components/input-error';
import RebootNavbar from '@/components/reboot-navbar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type SharedData } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/react';
import { ImagePlus, LoaderCircle, X } from 'lucide-react';
import { type FormEventHandler, useEffect, useRef, useState } from 'react';

// Beschrijft de invoervelden en fotobestanden van een aanmelding.
type DeviceForm = {
    type: string;
    brand: string;
    model: string;
    serial_number: string;
    condition: string;
    accessories: string;
    asking_price: string;
    photos: File[];
};

export default function CreateDevice() {
    const { auth } = usePage<SharedData>().props;
    const { data, setData, post, processing, errors, reset, progress } = useForm<DeviceForm>({
        type: '',
        brand: '',
        model: '',
        serial_number: '',
        condition: '',
        accessories: '',
        asking_price: '',
        photos: [],
    });
    const fileInput = useRef<HTMLInputElement>(null);
    const [previews, setPreviews] = useState<string[]>([]);
    const [photoError, setPhotoError] = useState('');

    // Maakt tijdelijke fotovoorbeelden en geeft de gebruikte URLs daarna weer vrij.
    useEffect(() => {
        const urls = data.photos.map((photo) => URL.createObjectURL(photo));
        setPreviews(urls);
        return () => urls.forEach((url) => URL.revokeObjectURL(url));
    }, [data.photos]);

    // Verstuurt ook de bestanden en leegt het formulier na een geslaagde aanmelding.
    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        post(route('devices.store'), {
            forceFormData: true,
            preserveScroll: 'errors',
            onSuccess: () => {
                reset();
                setPhotoError('');
                if (fileInput.current) fileInput.current.value = '';
            },
        });
    };

    const fieldClass =
        'h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-[#111827] focus-visible:outline-2 focus-visible:outline-emerald-600';
    // Verzamelt algemene fotofouten en fouten van afzonderlijke foto's.
    const photoErrors = Object.entries(errors).filter(([key]) => key === 'photos' || key.startsWith('photos.'));

    return (
        <div className="min-h-screen bg-[#F3F4F6] text-[#111827]">
            <Head title="Apparaat aanmelden | Reboot" />
            <RebootNavbar />
            <main className="mx-auto max-w-3xl px-4 py-8 sm:py-12">
                <h1 className="text-3xl font-bold">Apparaat aanmelden</h1>
                <p className="mt-2 text-slate-600">
                    Meld je apparaat aan op naam van {auth.user.name} ({auth.user.email}).
                </p>
                <form onSubmit={submit} className="mt-6 space-y-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
                    <fieldset disabled={processing} className="space-y-6">
                        <div className="space-y-3">
                            <Label htmlFor="photos">Foto’s van je apparaat</Label>
                            <p id="photos-help" className="text-sm text-slate-500">
                                Optioneel: maximaal 5 foto’s van 2 MB per foto (JPG, PNG of WebP). De eerste foto is de omslagfoto.
                            </p>
                            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                                {previews.map((preview, index) => (
                                    <div key={preview} className="relative">
                                        <img
                                            src={preview}
                                            alt={index === 0 ? 'Omslagfoto' : `Foto ${index + 1}`}
                                            className="aspect-square w-full rounded-xl border object-cover"
                                        />
                                        <button
                                            type="button"
                                            aria-label={`Foto ${index + 1} verwijderen`}
                                            onClick={() =>
                                                setData(
                                                    'photos',
                                                    data.photos.filter((_, photoIndex) => photoIndex !== index),
                                                )
                                            }
                                            className="absolute top-2 right-2 rounded-full bg-white p-2 shadow-sm"
                                        >
                                            <X className="size-4" aria-hidden="true" />
                                        </button>
                                        <p className="mt-1 text-sm">{index === 0 ? 'Omslagfoto' : `Foto ${index + 1}`}</p>
                                    </div>
                                ))}
                                {data.photos.length < 5 && (
                                    <button
                                        type="button"
                                        onClick={() => fileInput.current?.click()}
                                        className="flex aspect-square flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-slate-300 text-slate-600 hover:border-emerald-600"
                                    >
                                        <ImagePlus className="size-8" aria-hidden="true" />
                                        <span>Foto toevoegen</span>
                                    </button>
                                )}
                            </div>
                            <input
                                ref={fileInput}
                                id="photos"
                                type="file"
                                multiple
                                accept="image/jpeg,image/png,image/webp"
                                className="sr-only"
                                aria-describedby="photos-help"
                                onChange={(event) => {
                                    const photos = Array.from(event.target.files ?? []);
                                    event.target.value = '';
                                    if (data.photos.length + photos.length > 5) {
                                        setPhotoError('Je kunt maximaal vijf foto’s toevoegen.');
                                        return;
                                    }
                                    if (photos.some((photo) => photo.size > 2 * 1024 * 1024)) {
                                        setPhotoError('Elke foto mag maximaal 2 MB groot zijn.');
                                        return;
                                    }
                                    setPhotoError('');
                                    setData('photos', [...data.photos, ...photos]);
                                }}
                            />
                            <InputError message={photoError} />
                            {photoErrors.map(([key, message]) => (
                                <InputError key={key} message={message} />
                            ))}
                        </div>
                        <div className="grid gap-5 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="type">Type apparaat</Label>
                                <select
                                    id="type"
                                    required
                                    value={data.type}
                                    onChange={(event) => setData('type', event.target.value)}
                                    className={fieldClass}
                                    aria-invalid={!!errors.type}
                                    aria-describedby="type-error"
                                >
                                    <option value="">Selecteer type apparaat</option>
                                    <option value="console">Console</option>
                                    <option value="telefoon">Telefoon</option>
                                    <option value="laptops">Laptops</option>
                                </select>
                                <InputError id="type-error" message={errors.type} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="condition">Conditie</Label>
                                <select
                                    id="condition"
                                    required
                                    value={data.condition}
                                    onChange={(event) => setData('condition', event.target.value)}
                                    className={fieldClass}
                                    aria-invalid={!!errors.condition}
                                    aria-describedby="condition-error"
                                >
                                    <option value="">Selecteer conditie</option>
                                    <option value="gebruikt">Gebruikt</option>
                                    <option value="goed">Goed</option>
                                    <option value="nieuw">Nieuw</option>
                                </select>
                                <InputError id="condition-error" message={errors.condition} />
                            </div>
                            {(
                                [
                                    { key: 'brand', label: 'Merk' },
                                    { key: 'model', label: 'Model' },
                                    { key: 'serial_number', label: 'Serienummer' },
                                ] as const
                            ).map(({ key, label }) => (
                                <div key={key} className="space-y-2">
                                    <Label htmlFor={key}>{label}</Label>
                                    <Input
                                        id={key}
                                        required
                                        maxLength={100}
                                        value={data[key]}
                                        onChange={(event) => setData(key, event.target.value)}
                                        className={fieldClass}
                                        aria-invalid={!!errors[key]}
                                        aria-describedby={`${key}-error`}
                                    />
                                    <InputError id={`${key}-error`} message={errors[key]} />
                                </div>
                            ))}
                            <div className="space-y-2">
                                <Label htmlFor="asking_price">Vraagprijs (€)</Label>
                                <Input
                                    id="asking_price"
                                    required
                                    inputMode="decimal"
                                    placeholder="Bijvoorbeeld 125,50"
                                    value={data.asking_price}
                                    onChange={(event) => setData('asking_price', event.target.value)}
                                    className={fieldClass}
                                    aria-invalid={!!errors.asking_price}
                                    aria-describedby="asking-price-error"
                                />
                                <InputError id="asking-price-error" message={errors.asking_price} />
                            </div>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="accessories">Accessoires (optioneel)</Label>
                            <Input
                                id="accessories"
                                maxLength={2000}
                                placeholder="Bijvoorbeeld oplader, controller of geen"
                                value={data.accessories}
                                onChange={(event) => setData('accessories', event.target.value)}
                                className={fieldClass}
                                aria-invalid={!!errors.accessories}
                                aria-describedby="accessories-error"
                            />
                            <InputError id="accessories-error" message={errors.accessories} />
                        </div>
                    </fieldset>
                    {progress && (
                        <p role="status" className="text-sm text-slate-600">
                            Uploaden: {progress.percentage}%
                        </p>
                    )}
                    <Button type="submit" disabled={processing} className="h-12 w-full rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">
                        {processing && <LoaderCircle className="size-4 animate-spin" aria-hidden="true" />}
                        {processing ? 'Apparaat aanmelden…' : 'Apparaat aanmelden'}
                    </Button>
                </form>
            </main>
        </div>
    );
}
