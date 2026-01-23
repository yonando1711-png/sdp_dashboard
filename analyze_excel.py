import pandas as pd
import json

file_path = '/home/yudi/dev/sdp_dashboard/LotSerial Summary_20260119.xlsx'

try:
    # Read Sheet1 for data structure
    df_sheet1 = pd.read_excel(file_path, sheet_name='Sheet1', nrows=5)
    print("=== Sheet1 Columns ===")
    print(df_sheet1.columns.tolist())
    print("\n=== Sheet1 Sample Data ===")
    print(df_sheet1.to_json(orient='records'))

    # Read Summary sheet for rules (though user described them, seeing the raw data helps)
    df_summary = pd.read_excel(file_path, sheet_name='Summary', nrows=20)
    print("\n=== Summary Sheet Content ===")
    print(df_summary.values)

except Exception as e:
    print(f"Error: {e}")
