export enum ToastType {
    STORE_SUCCESS = 'STORE_SUCCESS',
    UPDATE_SUCCESS = 'UPDATE_SUCCESS',
    DELETE_SUCCESS = 'DELETE_SUCCESS',
    ERROR = 'ERROR',
}

export function getToastTitle(type: ToastType): string {
    switch (type) {
        case ToastType.STORE_SUCCESS:
            return 'Successfully created!';
        case ToastType.UPDATE_SUCCESS:
            return 'Successfully updated!';
        case ToastType.DELETE_SUCCESS:
            return 'Successfully deleted!';
        case ToastType.ERROR:
            return 'Something went wrong!';
    }
}

export function onSuccessToast(type: ToastType, title: string | null = null) {
    return {
        onSuccess: () => useToast().add({ title: title || getToastTitle(type) })
    };
}

export function onErrorToast(type: ToastType, title: string | null = null) {
    return {
        onError: () => useToast().add({ title: title || getToastTitle(type), color: 'error' })
    };
}
