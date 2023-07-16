import React, {createContext, useContext, useState} from "react";

const TitleContext = createContext();

export const useTitle = () => useContext(TitleContext);

const TitleProvider = ({ children }) => {
  const [title, setTitle] = useState("Set The Title");

  return (
    <TitleContext.Provider value={{ title, setTitle }}>
      {children}
    </TitleContext.Provider>
  );
};

export default TitleProvider;

