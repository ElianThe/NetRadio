<?php

namespace radio\net\domaine\dto;

class EmissionDTO extends DTO
{
    public int $id;
    public string $titre;
    public string $description;
    public string $theme;
    public string $photo;
    public ?bool $onDirect;
    public ?string $user;


    public function __construct ($p_titre, $p_description, $p_theme, $p_photo, $p_onDirect, $p_user) {
        $this->titre = $p_titre;
        $this->description = $p_description;
        $this->theme = $p_theme;
        $this->onDirect = $p_onDirect;
        $this->photo = $p_photo;
        $this->user = $p_user;
    }

    public function toArray () {
        return [
            'id' => $this->id,
            'titre' => $this->titre,
            'description'=> $this->description,
            'theme' => $this->theme,
            'onDirect' => $this->onDirect,
            'photo' => $this->photo,
            'user' => $this->user
        ];
    }
}