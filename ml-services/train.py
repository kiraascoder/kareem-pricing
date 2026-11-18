from pathlib import Path
import json
import joblib
import numpy as np
import pandas as pd
from sklearn.compose import ColumnTransformer
from sklearn.ensemble import RandomForestRegressor
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import OneHotEncoder
from features import FEATURES, make_features

ROOT = Path(__file__).resolve().parent
df = pd.read_csv(ROOT / 'dataset.csv', encoding='utf-8-sig')
required = {'jenis_acara', 'tanggal_pelaksanaan', 'harga_estimasi'}
if not required.issubset(df.columns):
    raise ValueError(f'Kolom dataset hilang: {required - set(df.columns)}')
if df[list(required)].isna().any().any():
    raise ValueError('Dataset mengandung nilai kosong pada kolom wajib')
if df['harga_estimasi'].le(0).any():
    raise ValueError('Harga harus positif')
rows = [make_features(row.jenis_acara, row.tanggal_pelaksanaan) for row in df.itertuples(index=False)]
X = pd.DataFrame(rows)[FEATURES]
y = df['harga_estimasi'].astype(float)
dates = pd.to_datetime(df['tanggal_pelaksanaan'])
order = np.argsort(dates.to_numpy(), kind='stable')
cut = int(len(df) * 0.8)
train_idx, test_idx = order[:cut], order[cut:]

pre = ColumnTransformer([
    ('category', OneHotEncoder(handle_unknown='ignore'), ['jenis_acara', 'hari_dalam_minggu', 'kategori_hari']),
    ('numeric', 'passthrough', ['tahun', 'bulan'])
])
model = Pipeline([('preprocess', pre), ('rf', RandomForestRegressor(
    n_estimators=300, min_samples_leaf=2, random_state=42, n_jobs=-1))])
model.fit(X.iloc[train_idx], y.iloc[train_idx])
pred = model.predict(X.iloc[test_idx])
metrics = {'rows': len(df), 'train_rows': len(train_idx), 'test_rows': len(test_idx),
           'mae': float(mean_absolute_error(y.iloc[test_idx], pred)),
           'rmse': float(np.sqrt(mean_squared_error(y.iloc[test_idx], pred))),
           'r2': float(r2_score(y.iloc[test_idx], pred)),
           'split': 'chronological_80_20', 'target': 'harga_estimasi'}
joblib.dump(model, ROOT / 'random_forest.joblib')
(ROOT / 'metrics.json').write_text(json.dumps(metrics, indent=2), encoding='utf-8')
print(json.dumps(metrics, indent=2))
