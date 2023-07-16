import React, { useEffect } from "react"
import { useTitle } from "../../contexts/titleContext";
import Box from "../../components/box";
import Title from "../../components/title";
import Table from "../../components/table";
import ModalInfo from "../../components/modalInfo";

const Whitelist = () => {

  const { setTitle } = useTitle();

  useEffect(() => {
    setTitle("List Of Installed Plugins");
  }, [])

  return (
    <Box>
      <div className="relative flex h-20 justify-between">
        <div className="relative z-10 flex px-2 lg:px-0 items-center">
          <Title>Whitelisted Plugins</Title>
        </div>
        <ModalInfo />
      </div>
      <div className="pb-10">
        <Table />
      </div>

    </Box>
  )
}

export default Whitelist;