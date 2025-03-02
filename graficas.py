import os
import json
import mysql.connector
import matplotlib.pyplot as plt

def load_config(file_path):
    with open(file_path, 'r') as file:
        config = json.load(file)
    return config

def fetch_data(config_path, query):
    try:
        config = load_config(config_path)
        connection = mysql.connector.connect(
            host=config["host"],
            user=config["user"],
            password=config["password"],
            database=config["database"]
        )
        cursor = connection.cursor()
        cursor.execute(query)
        results = cursor.fetchall()
        columns = [desc[0] for desc in cursor.description]
        cursor.close()
        connection.close()
        return [dict(zip(columns, row)) for row in results]
    except mysql.connector.Error as e:
        print(f"Database error: {e}")
        return None

def create_folder_structure(base_folder, virtualhost):
    # Create the base folder if it doesn't exist
    if not os.path.exists(base_folder):
        os.mkdir(base_folder)

    # Create a subfolder for each virtualhost
    subfolder_path = os.path.join(base_folder, virtualhost)
    if not os.path.exists(subfolder_path):
        os.mkdir(subfolder_path)

    return subfolder_path

def save_pie_chart(data, folder_path, chart_name):
    if not data:
        print(f"No data to create the pie chart for {chart_name}")
        return

    # Ensure that 'clave' and 'valor' keys exist, defaulting to 'Unknown' and 0 respectively if not
    labels = [entry.get('clave', 'Unknown') for entry in data]
    sizes = [entry.get('valor', 0) for entry in data]

    # Check the data for debugging
    print(f"Data for pie chart {chart_name}: {list(zip(labels, sizes))}")

    plt.figure()
    plt.pie(sizes, labels=labels, autopct='%1.1f%%', startangle=140)
    plt.axis('equal')  # Equal aspect ratio ensures the pie chart is a circle.
    chart_path = os.path.join(folder_path, f"{chart_name}.png")
    plt.savefig(chart_path)
    plt.close()

def save_bar_chart(data, folder_path, chart_name):
    if not data:
        print(f"No data to create the bar chart for {chart_name}")
        return

    # Ensure that 'clave' and 'valor' keys exist, defaulting to 'Unknown' and 0 respectively if not
    labels = [entry.get('clave', 'Unknown') for entry in data]
    values = [entry.get('valor', 0) for entry in data]

    # Check the data for debugging
    print(f"Data for bar chart {chart_name}: {list(zip(labels, values))}")

    plt.figure()
    plt.bar(labels, values)
    plt.xlabel('Clave')
    plt.ylabel('Valor')
    plt.title('Bar Chart')
    chart_path = os.path.join(folder_path, f"{chart_name}_bar.png")
    plt.savefig(chart_path)
    plt.close()

if __name__ == "__main__":
    config_file = "db_config.json"
    base_folder = "imagenes"

    sql_query = "SELECT * FROM virtualhosts;"
    data = fetch_data(config_file, sql_query)

    if data:
        if not os.path.exists(base_folder):
            os.mkdir(base_folder)

        for row in data:
            virtualhost = row['virtualhost']
            subfolder = create_folder_structure(base_folder, virtualhost)

            # Fetch and handle data for browsers
            data2 = fetch_data(config_file, f"CALL Navegadores('{virtualhost}');")
            if data2:
                save_pie_chart(data2, subfolder, f"{virtualhost}_browsers")

            # Fetch and handle data for visits per hour
            data2 = fetch_data(config_file, f"CALL VisitasPorHora('{virtualhost}');")
            if data2:
                save_bar_chart(data2, subfolder, f"{virtualhost}_visitasporhora")

            # Fetch and handle data for operating systems
            data2 = fetch_data(config_file, f"CALL SistemasOperativos('{virtualhost}');")
            if data2:
                save_pie_chart(data2, subfolder, f"{virtualhost}_sistemas_operativos")

            # Fetch and handle data for status codes
            data2 = fetch_data(config_file, f"CALL CodigosDeEstado('{virtualhost}');")
            if data2:
                save_pie_chart(data2, subfolder, f"{virtualhost}_codigos_de_estado")
                
            # Fetch and handle data for visits in the last 15 days
            data2 = fetch_data(config_file, f"CALL VisitasUltimos15Dias('{virtualhost}');")
            if data2:
                save_bar_chart(data2, subfolder, f"{virtualhost}_visitas_ultimos_15_dias")
                
            # Fetch and handle data for IPs in the last 15 days
            data2 = fetch_data(config_file, f"CALL IPs15ultimosdias('{virtualhost}');")
            if data2:
                save_bar_chart(data2, subfolder, f"{virtualhost}_IP_ultimos_15_dias")

        print(f"Charts saved in the '{base_folder}' folder.")
    else:
        print("No data retrieved or an error occurred.")
  