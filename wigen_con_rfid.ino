#include <Ethernet.h>
#include <MySQL_Connection.h>
#include <MySQL_Cursor.h>
#include <Wiegand.h>

#define PIN_D0 2
#define PIN_D1 3
#define PIN_RELE 5

byte mac[] = { 0xDE, 0xAD, 0xBE, 0xEF, 0xFE, 0xED };
IPAddress serverDB(91, 208, 207, 108);
char usuario[] = "uedwkrwyweha5rcy";
char pass[] = "PshcRgfZkFIulygInL5Q";
char db_name[] = "byfcaqpyah9l6i1xrpj2";

EthernetClient client;
MySQL_Connection conn((Client *)&client);

Wiegand wiegand;

void setup() {
  Serial.begin(9600);

  pinMode(PIN_D0, INPUT);
  pinMode(PIN_D1, INPUT);
  pinMode(PIN_RELE, OUTPUT);
  digitalWrite(PIN_RELE, HIGH);

  Serial.println("Test***Antes de agarrar internet");

  if (Ethernet.begin(mac) == 0) {
    Serial.println("Fallo al obtener IP, checar aqui");
    return;
  }
  conectarBD();

  wiegand.onReceive(datosRecibidos, "Recibiendo datos...");
  wiegand.onReceiveError(erroresRecibidos, "Error al leer tarjeta:");
  wiegand.begin(Wiegand::LENGTH_ANY, false);
}

void loop() {
  wiegand.flush();
  wiegand.setPin0State(digitalRead(PIN_D0));
  wiegand.setPin1State(digitalRead(PIN_D1));
}

void datosRecibidos(uint8_t* data, uint8_t bits, const char* message) {
  Serial.print(message);

  if (bits == 26) {
    uint32_t rawData = 0;
    for (uint8_t i = 0; i < 4; i++) {
      rawData = (rawData << 8) | data[i];
    }

    uint32_t cardNumber = (rawData >> 1) & 0xFFFFFF;

    Serial.print("Numero de tarjeta: ");
    Serial.println(cardNumber);

    if (comprobarDatosBD(cardNumber)) {
      activarRele();
    } else {
      Serial.println("Tarjeta no válida.");
      Serial.println("");
    }
  } else {
    Serial.println("No compatible.");
    Serial.println("");
  }
}

void erroresRecibidos(Wiegand::DataError error, uint8_t* rawData, uint8_t rawBits, const char* message) {
  Serial.print(message);
  Serial.print(" ");
  Serial.println(Wiegand::DataErrorStr(error));
}

void conectarBD() {
  Serial.println("Conectando a BD...");
  if (conn.connect(serverDB, 3306, usuario, pass)) {
    Serial.println("Conexión establecida.");
    MySQL_Cursor cur(&conn);
    char use_db[64];
    sprintf(use_db, "USE %s", db_name);
    cur.execute(use_db);
  } else {
    Serial.println("Error al conectar a BD.");
  }
}

bool comprobarDatosBD(uint32_t numeroTarjeta) {
  if (!conn.connected()) {
    Serial.println("Conexión perdida. Reintentando...");
    conectarBD();
    if (!conn.connected()) {
      Serial.println("Se perdio la conexion con la BD.");
      return false;
    }
  }

  char query[128];
  Serial.println("Consultando tarjeta...");
  sprintf(query, "SELECT * FROM tarjetas WHERE numero_identificacion='%lu'", numeroTarjeta);

  MySQL_Cursor cur(&conn);
  if (!cur.execute(query)) {
    Serial.println("Error al ejecutar query.");
    conn.close();
    return false;
  }

  column_names *cols = cur.get_columns();
  if (!cols) {
    Serial.println("Error al leer columnas de BD.");
    conn.close();
    return false;
  }

  row_values *row = cur.get_next_row();
  if (row) {
    Serial.print("Tarjeta valida y encontrada en BD");
    //Serial.println(row->values[0]);
    conn.close();
    return true;
  } else {
    Serial.println("No existe la tarjeta en BD.");
    conn.close();
    return false;
  }
}

void activarRele() {
  Serial.println("Activando rele...");
  digitalWrite(PIN_RELE, LOW);
  delay(5000);
  digitalWrite(PIN_RELE, HIGH);
  Serial.println("");
}