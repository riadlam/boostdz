import { forwardRef } from 'react';
import PhoneInput, { isValidPhoneNumber } from 'react-phone-number-input';
import 'react-phone-number-input/style.css';
import { cn } from '../../lib/cn';

const PhoneTextInput = forwardRef(function PhoneTextInput({ className, ...props }, ref) {
    return (
        <input
            {...props}
            ref={ref}
            className={cn('phone-input__text', className)}
        />
    );
});

/**
 * @param {{
 *   id?: string,
 *   value?: string,
 *   onChange: (value?: string) => void,
 *   disabled?: boolean,
 *   invalid?: boolean,
 *   defaultCountry?: import('react-phone-number-input').Country,
 * }} props
 */
export default function PhoneField({
    id,
    value,
    onChange,
    disabled = false,
    invalid = false,
    defaultCountry = 'DZ',
}) {
    return (
        <PhoneInput
            id={id}
            international
            defaultCountry={defaultCountry}
            countryCallingCodeEditable={false}
            value={value}
            onChange={onChange}
            disabled={disabled}
            inputComponent={PhoneTextInput}
            className={cn('phone-input__control dash-input', invalid && 'border-red-500/50')}
        />
    );
}

export function isValidPhone(value) {
    return Boolean(value) && isValidPhoneNumber(value);
}
