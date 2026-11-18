import pandas as pd
FEATURES = ['jenis_acara', 'tahun', 'bulan', 'hari_dalam_minggu', 'kategori_hari']
CATEGORIES = ['Workshop', 'Seminar', 'Family Gathering', 'Employee Gathering', 'Event Bisnis']

def make_features(category: str, date: str):
    dt = pd.to_datetime(date, format='%Y-%m-%d', errors='raise')
    if category not in CATEGORIES:
        raise ValueError(f'Jenis acara tidak didukung: {category}. Pilihan: {CATEGORIES}')
    return {'jenis_acara': category, 'tahun': int(dt.year), 'bulan': int(dt.month),
            'hari_dalam_minggu': dt.day_name(), 'kategori_hari': 'Weekend' if dt.weekday() >= 5 else 'Weekday'}
