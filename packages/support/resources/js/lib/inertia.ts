export enum ToastType {
    STORE_SUCCESS = 'STORE_SUCCESS',
    UPDATE_SUCCESS = 'UPDATE_SUCCESS',
    DELETE_SUCCESS = 'DELETE_SUCCESS',
    ERROR = 'ERROR',
}

export const getToastTitle = (type: ToastType): string => {
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
};

export const onSuccessToast = (type: ToastType, title: string | null = null) => ({
    onSuccess: () => useToast().add({ title: title || getToastTitle(type) }),
});

export const onErrorToast = (type: ToastType, title: string | null = null) => ({
    onError: () => useToast().add({ title: title || getToastTitle(type), color: 'error' }),
});
