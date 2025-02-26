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
    labels = [entry['clave'] for entry in data]
    sizes = [entry['valor'] for entry in data]

    plt.figure()
    plt.pie(sizes, labels=labels, autopct='%1.1f%%', startangle=140)
    plt.axis('equal')  # Equal aspect ratio ensures the pie chart is a circle.
    chart_path = os.path.join(folder_path, f"{chart_name}.png")
    plt.savefig(chart_path)
    plt.close()

def save_bar_chart(data, folder_path, chart_name):
    labels = [entry['clave'] for entry in data]
    values = [entry['valor'] for entry in data]

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

            data2 = fetch_data(config_file, f"CALL Navegadores('{virtualhost}');")
            if data2:
                save_pie_chart(data2, subfolder, f"{virtualhost}_browsers")

            data2 = fetch_data(config_file, f"CALL VisitasPorHora('{virtualhost}');")
            if data2:
                save_bar_chart(data2, subfolder, f"{virtualhost}_visitasporhora")

            data2 = fetch_data(config_file, f"CALL SistemasOperativos('{virtualhost}');")
            if data2:
                save_pie_chart(data2, subfolder, f"{virtualhost}_sistemas_operativos")

            data2 = fetch_data(config_file, f"CALL CodigosDeEstado('{virtualhost}');")
            if data2:
                save_pie_chart(data2, subfolder, f"{virtualhost}_codigos_de_estado")
                
            data2 = fetch_data(config_file, f"CALL VisitasUltimos15Dias('{virtualhost}');")
            if data2:
                save_bar_chart(data2, subfolder, f"{virtualhost}_visitas_ultimos_15_dias")
                
            data2 = fetch_data(config_file, f"CALL IPs15ultimosdias('{virtualhost}');")
            if data2:
                save_bar_chart(data2, subfolder, f"{virtualhost}_IP_ultimos_15_dias")

        print(f"Charts saved in the '{base_folder}' folder.")
    else:
        print("No data retrieved or an error occurred.")
