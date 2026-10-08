/**
 * Onderdeel: Herbruikbare foutmelding bij ongeldige formulierinvoer.
 * Eisen: RV-07, TE-04, TE-06.
 * Bouw: T-29 (invoercontrole), T-31 (herbruikbare onderdelen).
 * Geplande controle: T-30, T-32.
 */

import { cn } from '@/lib/utils';
import { HTMLAttributes } from 'react';

export default function InputError({ message, className = '', ...props }: HTMLAttributes<HTMLParagraphElement> & { message?: string }) {
    return message ? (
        <p {...props} className={cn('text-sm text-red-600 dark:text-red-400', className)}>
            {message}
        </p>
    ) : null;
}
