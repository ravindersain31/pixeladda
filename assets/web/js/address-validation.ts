interface Address {
    firstName?: string;
    lastName?: string;
    addressLine1?: string;
    addressLine2?: string;
    city?: string;
    state?: string;
    country?: string;
    zipcode?: string;
    phone?: string;
    email?: string;
    textUpdatesNumber?: string;
}

function generateValidationRules(typeOfAddress: string, formName: string = 'checkout') {
    const fieldMessages = {
        firstName: {
            required: true,
        },
        lastName: {
            required: true,
        },
        addressLine1: {
            required: true,
        },
        city: {
            required: true,
        },
        state: {
            required: true,
        },
        country: {
            required: true,
        },
        zipcode: {
            required: true,
        },
        phone: {
            required: true,
        },
        email: {
            required: true,
            email: true,
        }
    }
    let messages: {
        [key: string]: {}
    } = {};
    for (const [fieldName, fieldMessage] of Object.entries(fieldMessages)) {
        messages[makeFieldName(typeOfAddress, fieldName, formName)] = fieldMessage;
    }
    return messages;
}
window.generateValidationRules = generateValidationRules;

function generateValidationMessages(typeOfAddress: string, formName: string = 'checkout') {
    const fieldMessages = {
        firstName: {
            required: 'First name cannot be empty',
        },
        lastName: {
            required: 'Last name cannot be empty',
        },
        addressLine1: {
            required: 'Address cannot be empty',
        },
        city: {
            required: 'City cannot be empty',
        },
        state: {
            required: 'State cannot be empty',
        },
        country: {
            required: 'Country cannot be empty',
        },
        zipcode: {
            required: 'Zip code cannot be empty',
        },
        phone: {
            required: 'Phone number cannot be empty',
        },
        email: {
            required: 'Email address cannot be empty',
            email: 'Email address is not valid',
        }
    }
    let messages: {
        [key: string]: {}
    } = {};
    for (const [fieldName, fieldMessage] of Object.entries(fieldMessages)) {
        messages[makeFieldName(typeOfAddress, fieldName, formName)] = fieldMessage;
    }
    return messages;
}
window.generateValidationMessages = generateValidationMessages;

function makeFieldName(typeOfAddress: string, fieldName: string, formName: string = 'checkout') {
    return `${formName}[${typeOfAddress}][${fieldName}]`;
}


function saveAddressToLocalStorage(typeOfAddress: string, formName: string = 'checkout'): void {
    const fields: string[] = [
        'firstName', 'lastName', 'addressLine1', 'addressLine2',
        'city', 'state', 'country', 'zipcode', 'phone', 'email'
    ];
    let address: Address = {};
    fields.forEach(field => {
        const fieldName = makeFieldName(typeOfAddress, field, formName);
        const fieldElement = document.querySelector<HTMLInputElement>(`[name="${fieldName}"]`);
        if (fieldElement) {
            address[field as keyof Address] = fieldElement.value;
        }
    });
    // Save textUpdatesNumber separately
    const textUpdatesNumberField = document.querySelector<HTMLInputElement>(`#${formName}_textUpdatesNumber`);
    if (textUpdatesNumberField) {
        address.textUpdatesNumber = textUpdatesNumberField.value;
    }

    localStorage.setItem(`${formName}_${typeOfAddress}`, JSON.stringify(address));
}

function autofillAddressFromLocalStorage(typeOfAddress: string, formName: string = 'checkout'): void {
    const address = JSON.parse(localStorage.getItem(`${formName}_${typeOfAddress}`) || '{}') as Address;
    if (address) {
        for (const [field, value] of Object.entries(address)) {
            const fieldName = makeFieldName(typeOfAddress, field, formName);
            const fieldElement = document.querySelector<HTMLInputElement>(`[name="${fieldName}"]`);

            if(fieldElement && field === 'country') {
                fieldElement.value = value as string;
                fieldElement.dispatchEvent(new Event("change", {bubbles: true}));
            }else if(fieldElement) {
                fieldElement.value = value as string;
            }
        }
        const textUpdatesNumberField = document.querySelector<HTMLInputElement>(`#${formName}_textUpdatesNumber`);
        if (textUpdatesNumberField) {
            textUpdatesNumberField.value = address.textUpdatesNumber || '';
        }
    }
}

function clearAddressFromLocalStorage(formName: string = 'checkout'): void {
    const typesOfAddress: string[] = ['billingAddress', 'shippingAddress'];
    typesOfAddress.forEach(type => {
        localStorage.removeItem(`${formName}_${type}`);
    });
}

// Add event listeners to save address on change
document.querySelectorAll<HTMLInputElement | HTMLSelectElement>('input, select').forEach(element => {
    element.addEventListener('change', (event: Event) => {
        if (event.isTrusted) {
            // Save to local storage only if the change event is triggered by user input
            saveAddressToLocalStorage("billingAddress");
            saveAddressToLocalStorage("shippingAddress");
        }
    });
});

// window.addEventListener('popstate', () => {
//     clearAddressFromLocalStorage();
// });

document.addEventListener('DOMContentLoaded', () => {
    autofillAddressFromLocalStorage('shippingAddress');
    autofillAddressFromLocalStorage('billingAddress');
});

(window as any).saveAddressToLocalStorage = saveAddressToLocalStorage;
(window as any).autofillAddressFromLocalStorage = autofillAddressFromLocalStorage;
(window as any).clearAddressFromLocalStorage = clearAddressFromLocalStorage;
