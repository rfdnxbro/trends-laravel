export const formatDate = (date: string): string => {
    return new Date(date).toLocaleDateString('ja-JP', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

export const formatNumber = (num: number): string => {
    return new Intl.NumberFormat('ja-JP').format(num);
};

export const truncateText = (text: string, maxLength: number): string => {
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
};

export const debounce = <T extends (...args: any[]) => void>(
    func: T,
    delay: number
): (...args: Parameters<T>) => void => {
    let timeoutId: ReturnType<typeof setTimeout>;
    return (...args: Parameters<T>) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => func(...args), delay);
    };
};